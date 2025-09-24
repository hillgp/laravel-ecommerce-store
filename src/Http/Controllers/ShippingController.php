<?php

namespace LaravelEcommerceStore\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use LaravelEcommerceStore\Models\Cart;
use LaravelEcommerceStore\Models\Customer;
use LaravelEcommerceStore\Services\ShippingService;

class ShippingController extends Controller
{
    protected ShippingService $shippingService;

    public function __construct(ShippingService $shippingService)
    {
        $this->shippingService = $shippingService;
        $this->middleware('auth');
    }

    /**
     * Calcular fretes disponíveis.
     */
    public function calculate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'cep_destino' => 'required|string|size:9',
            'cart_id' => 'nullable|integer|exists:carts,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dados inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $customer = Auth::user()->customer;

            // Buscar ou criar carrinho
            $cart = $this->getCart($request->cart_id, $customer);

            // Calcular fretes
            $fretes = $this->shippingService->calcularFretes(
                $cart,
                $request->cep_destino,
                $customer
            );

            return response()->json([
                'success' => true,
                'fretes' => $fretes,
                'cart' => [
                    'id' => $cart->id,
                    'total_items' => $cart->items->count(),
                    'total_weight' => $cart->items->sum(function ($item) {
                        return ($item->product->weight ?? 0.3) * $item->quantity;
                    }),
                    'total_value' => $cart->total,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao calcular fretes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Selecionar método de envio.
     */
    public function select(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'calculo_id' => 'required|integer|exists:shipping_calculations,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dados inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $customer = Auth::user()->customer;

            $calculo = $this->shippingService->selecionarMetodoEnvio(
                $request->calculo_id,
                $customer->id
            );

            if (!$calculo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cálculo de frete não encontrado'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Método de envio selecionado com sucesso',
                'calculo' => [
                    'id' => $calculo->id,
                    'transportadora' => $calculo->shippingMethod->carrier->name,
                    'metodo' => $calculo->shippingMethod->name,
                    'custo' => $calculo->calculated_cost,
                    'prazo' => $calculo->estimated_days,
                    'rastreamento' => $calculo->tracking_code,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao selecionar método de envio',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obter cálculos de frete recentes.
     */
    public function history(Request $request): JsonResponse
    {
        $customer = Auth::user()->customer;
        $cart = $this->getCart($request->cart_id, $customer);

        $calculos = $this->shippingService->getCalculosRecentes($cart);

        return response()->json([
            'success' => true,
            'calculos' => $calculos
        ]);
    }

    /**
     * Obter transportadoras disponíveis.
     */
    public function carriers(): JsonResponse
    {
        $carriers = $this->shippingService->getTransportadorasAtivas();

        return response()->json([
            'success' => true,
            'carriers' => $carriers
        ]);
    }

    /**
     * Simular frete (para usuários não logados).
     */
    public function simulate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'cep_origem' => 'required|string|size:9',
            'cep_destino' => 'required|string|size:9',
            'peso' => 'required|numeric|min:0.1',
            'valor' => 'required|numeric|min:0',
            'comprimento' => 'nullable|numeric|min:1',
            'largura' => 'nullable|numeric|min:1',
            'altura' => 'nullable|numeric|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dados inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Criar carrinho temporário para simulação
            $cart = new Cart([
                'session_id' => session()->getId(),
                'items' => collect([[
                    'product' => (object) [
                        'weight' => $request->peso,
                        'length' => $request->comprimento ?? 20,
                        'width' => $request->largura ?? 15,
                        'height' => $request->altura ?? 5,
                    ],
                    'quantity' => 1,
                ]]),
                'total' => $request->valor,
            ]);

            // Calcular fretes
            $fretes = $this->shippingService->calcularFretes(
                $cart,
                $request->cep_destino
            );

            return response()->json([
                'success' => true,
                'fretes' => $fretes,
                'simulacao' => [
                    'cep_origem' => $request->cep_origem,
                    'cep_destino' => $request->cep_destino,
                    'peso' => $request->peso,
                    'valor' => $request->valor,
                    'dimensoes' => [
                        'comprimento' => $request->comprimento ?? 20,
                        'largura' => $request->largura ?? 15,
                        'altura' => $request->altura ?? 5,
                    ],
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao simular frete',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obter cálculo específico.
     */
    public function show(int $id): JsonResponse
    {
        $customer = Auth::user()->customer;

        $calculo = $this->shippingService->getCalculo($id, $customer->id);

        if (!$calculo) {
            return response()->json([
                'success' => false,
                'message' => 'Cálculo não encontrado'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'calculo' => [
                'id' => $calculo->id,
                'origem' => $calculo->formatted_postal_code_from,
                'destino' => $calculo->formatted_postal_code_to,
                'peso' => $calculo->total_weight,
                'valor' => $calculo->total_value,
                'metodo' => [
                    'id' => $calculo->shippingMethod->id,
                    'nome' => $calculo->shippingMethod->name,
                    'transportadora' => $calculo->shippingMethod->carrier->name,
                    'descricao' => $calculo->shippingMethod->description,
                ],
                'custo' => $calculo->calculated_cost,
                'prazo' => $calculo->estimated_days,
                'detalhes' => $calculo->calculation_details,
                'rastreamento' => $calculo->tracking_code,
                'criado_em' => $calculo->calculated_at,
            ]
        ]);
    }

    /**
     * Obter ou criar carrinho do cliente.
     */
    protected function getCart(?int $cartId, Customer $customer): Cart
    {
        if ($cartId) {
            $cart = Cart::where('id', $cartId)
                ->where(function ($query) use ($customer) {
                    $query->where('customer_id', $customer->id)
                          ->orWhere('session_id', session()->getId());
                })
                ->first();

            if ($cart) {
                return $cart;
            }
        }

        // Criar novo carrinho
        return Cart::create([
            'customer_id' => $customer->id,
            'session_id' => session()->getId(),
        ]);
    }

    /**
     * Inicializar transportadoras padrão.
     */
    public function initializeCarriers(): JsonResponse
    {
        try {
            $correios = $this->shippingService->criarTransportadoraCorreios();
            $this->shippingService->criarMetodosCorreios($correios);

            return response()->json([
                'success' => true,
                'message' => 'Transportadoras inicializadas com sucesso'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao inicializar transportadoras',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}