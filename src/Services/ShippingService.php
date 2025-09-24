<?php

namespace LaravelEcommerceStore\Services;

use LaravelEcommerceStore\Models\ShippingCarrier;
use LaravelEcommerceStore\Models\ShippingMethod;
use LaravelEcommerceStore\Models\ShippingCalculation;
use LaravelEcommerceStore\Models\Cart;
use LaravelEcommerceStore\Models\Customer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ShippingService
{
    protected CorreiosService $correiosService;

    public function __construct(CorreiosService $correiosService)
    {
        $this->correiosService = $correiosService;
    }

    /**
     * Calcular fretes disponíveis para um carrinho.
     */
    public function calcularFretes(Cart $cart, string $cepDestino, ?Customer $customer = null): array
    {
        $cepOrigem = config('app.store_postal_code', '01310100'); // CEP da loja

        // Calcular peso e valor total
        $pesoTotal = $cart->items->sum(function ($item) {
            return ($item->product->weight ?? 0.3) * $item->quantity;
        });

        $valorTotal = $cart->total;

        // Calcular dimensões aproximadas
        $dimensoes = $this->calcularDimensoes($cart);

        // Buscar métodos de envio disponíveis
        $metodosDisponiveis = $this->getMetodosDisponiveis($cepOrigem, $cepDestino, $pesoTotal, $valorTotal);

        $calculos = [];

        foreach ($metodosDisponiveis as $metodo) {
            $calculo = $this->calcularFrete(
                $cepOrigem,
                $cepDestino,
                $pesoTotal,
                $valorTotal,
                $dimensoes,
                $metodo,
                $customer ? $customer->id : null,
                $cart->id
            );

            if ($calculo) {
                $calculos[] = $calculo;
            }
        }

        // Ordenar por custo
        usort($calculos, function ($a, $b) {
            return $a['custo'] <=> $b['custo'];
        });

        return $calculos;
    }

    /**
     * Obter métodos de envio disponíveis.
     */
    protected function getMetodosDisponiveis(string $cepOrigem, string $cepDestino, float $peso, float $valor): array
    {
        return ShippingMethod::with('carrier')
            ->where('is_active', true)
            ->where(function ($query) use ($peso, $valor) {
                $query->where(function ($q) {
                    $q->whereNull('min_weight')
                      ->orWhere('min_weight', '<=', 0);
                })
                ->orWhere(function ($q) use ($peso) {
                    $q->where('min_weight', '<=', $peso)
                      ->where(function ($q2) use ($peso) {
                          $q2->whereNull('max_weight')
                             ->orWhere('max_weight', '>=', $peso);
                      });
                });
            })
            ->where(function ($query) use ($valor) {
                $query->where(function ($q) {
                    $q->whereNull('min_value')
                      ->orWhere('min_value', '<=', 0);
                })
                ->orWhere(function ($q) use ($valor) {
                    $q->where('min_value', '<=', $valor)
                      ->where(function ($q2) use ($valor) {
                          $q2->whereNull('max_value')
                             ->orWhere('max_value', '>=', $valor);
                      });
                });
            })
            ->whereHas('carrier', function ($query) {
                $query->where('is_active', true);
            })
            ->get()
            ->filter(function ($metodo) use ($cepDestino) {
                return $metodo->supportsPostalCode($cepDestino) &&
                       $metodo->carrier->supportsPostalCode($cepDestino);
            })
            ->toArray();
    }

    /**
     * Calcular frete para um método específico.
     */
    protected function calcularFrete(
        string $cepOrigem,
        string $cepDestino,
        float $peso,
        float $valor,
        array $dimensoes,
        array $metodo,
        ?int $customerId,
        int $cartId
    ): ?array {
        try {
            // Calcular distância
            $distancia = $this->correiosService->calcularDistancia($cepOrigem, $cepDestino);

            // Calcular custo
            $detalhesCusto = $metodo['base_cost'] + ($peso * $metodo['cost_per_kg']);

            if ($distancia && $metodo['cost_per_km'] > 0) {
                $detalhesCusto += ($distancia * $metodo['cost_per_km']);
            }

            // Seguro
            $seguro = 0;
            if ($metodo['requires_insurance'] && $valor > 0) {
                $seguro = $valor * $metodo['insurance_rate'];
                $detalhesCusto += $seguro;
            }

            // Criar registro de cálculo
            $calculo = ShippingCalculation::create([
                'session_id' => session()->getId(),
                'customer_id' => $customerId,
                'cart_id' => $cartId,
                'postal_code_from' => preg_replace('/\D/', '', $cepOrigem),
                'postal_code_to' => preg_replace('/\D/', '', $cepDestino),
                'total_weight' => $peso,
                'total_value' => $valor,
                'total_volume' => $dimensoes['volume'] ?? null,
                'dimensions' => $dimensoes,
                'selected_method_id' => $metodo['id'],
                'calculated_cost' => $detalhesCusto,
                'estimated_days' => $metodo['estimated_days_min'] ?? 3,
                'calculation_details' => [
                    'peso' => $peso,
                    'distancia' => $distancia,
                    'dimensoes' => $dimensoes,
                    'custo_base' => $metodo['base_cost'],
                    'custo_peso' => $peso * $metodo['cost_per_kg'],
                    'custo_distancia' => $distancia ? ($distancia * $metodo['cost_per_km']) : 0,
                    'seguro' => $seguro,
                ],
                'calculated_at' => now(),
            ]);

            return [
                'id' => $calculo->id,
                'transportadora' => $metodo['carrier']['name'],
                'metodo' => $metodo['name'],
                'descricao' => $metodo['description'],
                'custo' => $detalhesCusto,
                'prazo' => $metodo['estimated_days_min'] ?? 3,
                'prazo_max' => $metodo['estimated_days_max'] ?? null,
                'detalhes' => $calculo->calculation_details,
            ];

        } catch (\Exception $e) {
            Log::error('Erro ao calcular frete', [
                'metodo_id' => $metodo['id'],
                'error' => $e->getMessage()
            ]);

            return null;
        }
    }

    /**
     * Calcular dimensões aproximadas do carrinho.
     */
    protected function calcularDimensoes(Cart $cart): array
    {
        $dimensoes = [
            'comprimento' => 0,
            'largura' => 0,
            'altura' => 0,
            'peso' => 0,
            'volume' => 0,
        ];

        foreach ($cart->items as $item) {
            $produto = $item->product;

            // Usar dimensões do produto ou valores padrão
            $comp = $produto->length ?? 20; // cm
            $larg = $produto->width ?? 15;  // cm
            $alt = $produto->height ?? 5;   // cm
            $peso = ($produto->weight ?? 0.3) * $item->quantity; // kg

            // Calcular dimensões totais (aproximado)
            $dimensoes['comprimento'] = max($dimensoes['comprimento'], $comp);
            $dimensoes['largura'] += $larg;
            $dimensoes['altura'] = max($dimensoes['altura'], $alt);
            $dimensoes['peso'] += $peso;
        }

        // Calcular volume em m³
        $dimensoes['volume'] = ($dimensoes['comprimento'] * $dimensoes['largura'] * $dimensoes['altura']) / 1000000;

        return $dimensoes;
    }

    /**
     * Selecionar método de envio para um pedido.
     */
    public function selecionarMetodoEnvio(int $calculoId, ?int $customerId = null): ?ShippingCalculation
    {
        $calculo = ShippingCalculation::where('id', $calculoId)
            ->when($customerId, function ($query) use ($customerId) {
                $query->where('customer_id', $customerId);
            })
            ->first();

        if (!$calculo) {
            return null;
        }

        // Atualizar cálculo como selecionado
        $calculo->update(['selected_method_id' => $calculo->selected_method_id]);

        return $calculo;
    }

    /**
     * Obter cálculo de frete por ID.
     */
    public function getCalculo(int $calculoId, ?int $customerId = null): ?ShippingCalculation
    {
        return ShippingCalculation::when($customerId, function ($query) use ($customerId) {
                $query->where('customer_id', $customerId);
            })
            ->with(['shippingMethod.carrier'])
            ->find($calculoId);
    }

    /**
     * Obter cálculos recentes para um carrinho.
     */
    public function getCalculosRecentes(Cart $cart, int $minutos = 30): array
    {
        return ShippingCalculation::where('cart_id', $cart->id)
            ->where('calculated_at', '>=', now()->subMinutes($minutos))
            ->with(['shippingMethod.carrier'])
            ->orderBy('calculated_cost')
            ->get()
            ->toArray();
    }

    /**
     * Limpar cálculos antigos.
     */
    public function limparCalculosAntigos(int $horas = 24): int
    {
        return ShippingCalculation::where('calculated_at', '<', now()->subHours($horas))
            ->delete();
    }

    /**
     * Obter transportadoras ativas.
     */
    public function getTransportadorasAtivas(): array
    {
        return ShippingCarrier::active()
            ->ordered()
            ->with('activeShippingMethods')
            ->get()
            ->toArray();
    }

    /**
     * Criar transportadora padrão (Correios).
     */
    public function criarTransportadoraCorreios(): ShippingCarrier
    {
        return ShippingCarrier::firstOrCreate(
            ['code' => 'CORREIOS'],
            [
                'name' => 'Correios',
                'tracking_url' => 'https://rastreamento.correios.com.br/app/resultado.php?objeto=:tracking_code',
                'is_active' => true,
                'settings' => [
                    'api_key' => null,
                    'contrato' => null,
                    'cartao_postagem' => null,
                ],
                'supported_regions' => [
                    ['type' => 'country', 'value' => 'BR']
                ],
                'sort_order' => 1,
            ]
        );
    }

    /**
     * Criar métodos de envio padrão para Correios.
     */
    public function criarMetodosCorreios(ShippingCarrier $carrier): void
    {
        $metodos = [
            [
                'name' => 'PAC',
                'code' => 'PAC',
                'description' => 'Encomenda Normal - Econômico',
                'base_cost' => 15.00,
                'cost_per_kg' => 2.00,
                'estimated_days_min' => 5,
                'estimated_days_max' => 15,
                'max_weight' => 30,
                'requires_insurance' => true,
                'insurance_rate' => 0.01,
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'SEDEX',
                'code' => 'SEDEX',
                'description' => 'Encomenda Expressa - Rápido',
                'base_cost' => 25.00,
                'cost_per_kg' => 3.00,
                'estimated_days_min' => 1,
                'estimated_days_max' => 3,
                'max_weight' => 30,
                'requires_insurance' => true,
                'insurance_rate' => 0.01,
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Mini Envios',
                'code' => 'MINI_ENVIOS',
                'description' => 'Para produtos pequenos e leves',
                'base_cost' => 10.00,
                'cost_per_kg' => 1.50,
                'estimated_days_min' => 3,
                'estimated_days_max' => 7,
                'max_weight' => 2,
                'requires_insurance' => false,
                'is_active' => true,
                'sort_order' => 3,
            ],
        ];

        foreach ($metodos as $metodo) {
            ShippingMethod::firstOrCreate(
                ['carrier_id' => $carrier->id, 'code' => $metodo['code']],
                $metodo
            );
        }
    }
}