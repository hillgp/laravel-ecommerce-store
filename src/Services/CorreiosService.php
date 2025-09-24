<?php

namespace LaravelEcommerceStore\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CorreiosService
{
    protected string $baseUrl = 'https://viacep.com.br/ws';
    protected int $timeout = 10;
    protected int $cacheMinutes = 1440; // 24 horas

    /**
     * Consultar CEP via API ViaCEP (gratuita).
     */
    public function consultarCep(string $cep): ?array
    {
        // Remove formatação do CEP
        $cep = preg_replace('/\D/', '', $cep);

        if (strlen($cep) !== 8) {
            throw new \InvalidArgumentException('CEP deve ter 8 dígitos');
        }

        $cacheKey = "cep_{$cep}";

        return Cache::remember($cacheKey, $this->cacheMinutes, function () use ($cep) {
            try {
                $response = Http::timeout($this->timeout)
                    ->get("{$this->baseUrl}/{$cep}/json/");

                if ($response->successful()) {
                    $data = $response->json();

                    if (isset($data['erro']) && $data['erro'] === true) {
                        return null;
                    }

                    return $this->formatCepData($data);
                }

                Log::warning('Erro ao consultar CEP', [
                    'cep' => $cep,
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);

                return null;

            } catch (\Exception $e) {
                Log::error('Erro ao consultar CEP', [
                    'cep' => $cep,
                    'error' => $e->getMessage()
                ]);

                return null;
            }
        });
    }

    /**
     * Formatar dados do CEP para uso interno.
     */
    protected function formatCepData(array $data): array
    {
        return [
            'cep' => $data['cep'] ?? null,
            'logradouro' => $data['logradouro'] ?? null,
            'complemento' => $data['complemento'] ?? null,
            'bairro' => $data['bairro'] ?? null,
            'localidade' => $data['localidade'] ?? null,
            'uf' => $data['uf'] ?? null,
            'ibge' => $data['ibge'] ?? null,
            'gia' => $data['gia'] ?? null,
            'ddd' => $data['ddd'] ?? null,
            'siafi' => $data['siafi'] ?? null,
            'consultado_em' => now()->toDateTimeString(),
        ];
    }

    /**
     * Validar formato do CEP.
     */
    public function validarCep(string $cep): bool
    {
        $cep = preg_replace('/\D/', '', $cep);
        return strlen($cep) === 8 && is_numeric($cep);
    }

    /**
     * Formatar CEP para exibição.
     */
    public function formatarCep(string $cep): string
    {
        $cep = preg_replace('/\D/', '', $cep);

        if (strlen($cep) === 8) {
            return substr($cep, 0, 5) . '-' . substr($cep, 5);
        }

        return $cep;
    }

    /**
     * Buscar CEPs por endereço (reverse geocoding).
     */
    public function buscarCepPorEndereco(string $estado, string $cidade, string $logradouro): array
    {
        $cacheKey = "endereco_{$estado}_{$cidade}_{$logradouro}";

        return Cache::remember($cacheKey, $this->cacheMinutes, function () use ($estado, $cidade, $logradouro) {
            try {
                $response = Http::timeout($this->timeout)
                    ->get("{$this->baseUrl}/{$estado}/{$cidade}/{$logradouro}/json/");

                if ($response->successful()) {
                    $data = $response->json();

                    return array_map(function ($item) {
                        return $this->formatCepData($item);
                    }, $data);
                }

                return [];

            } catch (\Exception $e) {
                Log::error('Erro ao buscar CEP por endereço', [
                    'estado' => $estado,
                    'cidade' => $cidade,
                    'logradouro' => $logradouro,
                    'error' => $e->getMessage()
                ]);

                return [];
            }
        });
    }

    /**
     * Calcular distância entre dois CEPs (aproximada).
     */
    public function calcularDistancia(string $cepOrigem, string $cepDestino): ?float
    {
        $cepOrigem = preg_replace('/\D/', '', $cepOrigem);
        $cepDestino = preg_replace('/\D/', '', $cepDestino);

        $cacheKey = "distancia_{$cepOrigem}_{$cepDestino}";

        return Cache::remember($cacheKey, $this->cacheMinutes, function () use ($cepOrigem, $cepDestino) {
            try {
                // Consulta dados dos CEPs
                $origem = $this->consultarCep($cepOrigem);
                $destino = $this->consultarCep($cepDestino);

                if (!$origem || !$destino) {
                    return null;
                }

                // Cálculo aproximado usando fórmula de Haversine
                // Considerando apenas a diferença de latitude/longitude
                $lat1 = $this->cepToLatitude($cepOrigem);
                $lon1 = $this->cepToLongitude($cepOrigem);
                $lat2 = $this->cepToLatitude($cepDestino);
                $lon2 = $this->cepToLongitude($cepDestino);

                if (!$lat1 || !$lon1 || !$lat2 || !$lon2) {
                    return null;
                }

                $earthRadius = 6371; // Raio da Terra em km

                $dLat = deg2rad($lat2 - $lat1);
                $dLon = deg2rad($lon2 - $lon1);

                $a = sin($dLat / 2) * sin($dLat / 2) +
                     cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
                     sin($dLon / 2) * sin($dLon / 2);

                $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

                return $earthRadius * $c;

            } catch (\Exception $e) {
                Log::error('Erro ao calcular distância entre CEPs', [
                    'cep_origem' => $cepOrigem,
                    'cep_destino' => $cepDestino,
                    'error' => $e->getMessage()
                ]);

                return null;
            }
        });
    }

    /**
     * Converter CEP para latitude aproximada (método simplificado).
     */
    protected function cepToLatitude(string $cep): ?float
    {
        // Esta é uma conversão aproximada baseada em padrões brasileiros
        // Para uma solução mais precisa, seria necessário usar uma API de geocoding

        $cep = preg_replace('/\D/', '', $cep);

        if (strlen($cep) !== 8) {
            return null;
        }

        // Mapeamento aproximado de regiões
        $regionMapping = [
            '01' => -23.5, // SP
            '02' => -23.5, // SP
            '03' => -22.9, // RJ
            '04' => -22.9, // RJ
            '05' => -20.3, // MG
            '06' => -20.3, // MG
            '07' => -15.8, // DF/GO
            '08' => -15.8, // DF/GO
            '09' => -25.4, // RS
            '10' => -25.4, // RS
            '11' => -22.1, // MS/MT
            '12' => -22.1, // MS/MT
            '13' => -23.5, // SP
            '14' => -23.5, // SP
            '15' => -23.5, // SP
            '16' => -20.3, // MG
            '17' => -20.3, // MG
            '18' => -22.1, // MS/MT
            '19' => -22.1, // MS/MT
            '20' => -3.1,  // AM
            '21' => -3.1,  // AM
            '22' => -3.1,  // AM
            '23' => -3.1,  // AM
            '24' => -3.1,  // AM
            '25' => -3.1,  // AM
            '26' => -3.1,  // AM
            '27' => -3.1,  // AM
            '28' => -3.1,  // AM
            '29' => -3.1,  // AM
        ];

        $region = substr($cep, 0, 2);
        $baseLat = $regionMapping[$region] ?? -15.8; // Brasília como fallback

        // Adicionar variação aleatória para simular diferentes pontos na região
        return $baseLat + (mt_rand(-50, 50) / 100);
    }

    /**
     * Converter CEP para longitude aproximada.
     */
    protected function cepToLongitude(string $cep): ?float
    {
        $cep = preg_replace('/\D/', '', $cep);

        if (strlen($cep) !== 8) {
            return null;
        }

        $region = substr($cep, 0, 2);
        $baseLon = -47.9; // Valor padrão para Brasil

        // Ajustes por região
        $regionAdjustments = [
            '01' => -46.6, // SP
            '02' => -46.6, // SP
            '03' => -43.2, // RJ
            '04' => -43.2, // RJ
            '05' => -44.0, // MG
            '06' => -44.0, // MG
            '07' => -47.9, // DF/GO
            '08' => -47.9, // DF/GO
            '09' => -51.2, // RS
            '10' => -51.2, // RS
            '11' => -54.5, // MS/MT
            '12' => -54.5, // MS/MT
            '13' => -46.6, // SP
            '14' => -46.6, // SP
            '15' => -46.6, // SP
            '16' => -44.0, // MG
            '17' => -44.0, // MG
            '18' => -54.5, // MS/MT
            '19' => -54.5, // MS/MT
            '20' => -60.0, // AM
            '21' => -60.0, // AM
            '22' => -60.0, // AM
            '23' => -60.0, // AM
            '24' => -60.0, // AM
            '25' => -60.0, // AM
            '26' => -60.0, // AM
            '27' => -60.0, // AM
            '28' => -60.0, // AM
            '29' => -60.0, // AM
        ];

        $baseLon = $regionAdjustments[$region] ?? $baseLon;

        // Adicionar variação
        return $baseLon + (mt_rand(-50, 50) / 100);
    }

    /**
     * Limpar cache de CEP.
     */
    public function limparCacheCep(string $cep): void
    {
        $cep = preg_replace('/\D/', '', $cep);
        Cache::forget("cep_{$cep}");
    }

    /**
     * Limpar todo o cache de CEPs.
     */
    public function limparTodoCache(): void
    {
        Cache::flush();
    }
}