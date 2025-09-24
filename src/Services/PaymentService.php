<?php

namespace LaravelEcommerce\Store\Services;

use LaravelEcommerce\Store\Models\Order;
use LaravelEcommerce\Store\Models\PaymentTransaction;
use LaravelEcommerce\Store\Models\PaymentGatewayConfig;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class PaymentService
{
    /**
     * Processa o pagamento de um pedido
     */
    public function processPayment(Order $order, array $paymentData = []): PaymentTransaction
    {
        $gateway = $paymentData['gateway'] ?? $this->getDefaultGateway();

        // Valida se o gateway está configurado
        $this->validateGateway($gateway);

        // Cria a transação
        $transaction = PaymentTransaction::create([
            'order_id' => $order->id,
            'gateway' => $gateway,
            'status' => 'pending',
            'type' => 'payment',
            'amount' => $order->total,
            'currency' => $order->currency,
            'payment_method' => $paymentData['payment_method'] ?? null,
            'meta_data' => [
                'payment_data' => $paymentData,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ],
        ]);

        try {
            // Processa o pagamento com o gateway específico
            $result = $this->processWithGateway($transaction, $paymentData);

            // Atualiza a transação com o resultado
            $transaction->update([
                'status' => $result['status'],
                'external_id' => $result['external_id'] ?? null,
                'gateway_response' => $result['response'] ?? null,
                'processed_at' => now(),
            ]);

            // Atualiza o status do pedido baseado no resultado
            if ($result['status'] === 'completed') {
                $order->updatePaymentStatus('paid', $transaction->transaction_id, $result['response']);
            } elseif (in_array($result['status'], ['failed', 'cancelled'])) {
                $order->updatePaymentStatus('failed');
            }

            Log::info('Pagamento processado', [
                'transaction_id' => $transaction->transaction_id,
                'order_id' => $order->id,
                'gateway' => $gateway,
                'status' => $result['status'],
                'amount' => $order->total,
            ]);

        } catch (\Exception $e) {
            $transaction->update([
                'status' => 'failed',
                'notes' => $e->getMessage(),
                'processed_at' => now(),
            ]);

            Log::error('Erro no processamento do pagamento', [
                'transaction_id' => $transaction->transaction_id,
                'order_id' => $order->id,
                'gateway' => $gateway,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }

        return $transaction;
    }

    /**
     * Processa o pagamento com um gateway específico
     */
    protected function processWithGateway(PaymentTransaction $transaction, array $paymentData): array
    {
        $gateway = $transaction->gateway;
        $config = PaymentGatewayConfig::getGatewayConfig($gateway);

        return match ($gateway) {
            'stripe' => $this->processStripePayment($transaction, $paymentData, $config),
            'mercadopago' => $this->processMercadoPagoPayment($transaction, $paymentData, $config),
            'pagseguro' => $this->processPagSeguroPayment($transaction, $paymentData, $config),
            'paypal' => $this->processPayPalPayment($transaction, $paymentData, $config),
            'manual' => $this->processManualPayment($transaction, $paymentData, $config),
            default => throw new \Exception("Gateway '{$gateway}' não suportado"),
        };
    }

    /**
     * Processa pagamento com Stripe
     */
    protected function processStripePayment(PaymentTransaction $transaction, array $paymentData, array $config): array
    {
        // Implementação do Stripe
        // Por enquanto retorna simulação
        return [
            'status' => 'completed',
            'external_id' => 'stripe_' . $transaction->id,
            'response' => [
                'id' => 'stripe_' . $transaction->id,
                'status' => 'succeeded',
                'amount' => $transaction->amount * 100, // Stripe usa centavos
            ],
        ];
    }

    /**
     * Processa pagamento com Mercado Pago
     */
    protected function processMercadoPagoPayment(PaymentTransaction $transaction, array $paymentData, array $config): array
    {
        // Implementação do Mercado Pago
        // Por enquanto retorna simulação
        return [
            'status' => 'completed',
            'external_id' => 'mp_' . $transaction->id,
            'response' => [
                'id' => 'mp_' . $transaction->id,
                'status' => 'approved',
                'amount' => $transaction->amount,
            ],
        ];
    }

    /**
     * Processa pagamento com PagSeguro
     */
    protected function processPagSeguroPayment(PaymentTransaction $transaction, array $paymentData, array $config): array
    {
        // Implementação do PagSeguro
        // Por enquanto retorna simulação
        return [
            'status' => 'completed',
            'external_id' => 'ps_' . $transaction->id,
            'response' => [
                'code' => 'ps_' . $transaction->id,
                'status' => 'PAID',
                'grossAmount' => $transaction->amount,
            ],
        ];
    }

    /**
     * Processa pagamento com PayPal
     */
    protected function processPayPalPayment(PaymentTransaction $transaction, array $paymentData, array $config): array
    {
        // Implementação do PayPal
        // Por enquanto retorna simulação
        return [
            'status' => 'completed',
            'external_id' => 'pp_' . $transaction->id,
            'response' => [
                'id' => 'pp_' . $transaction->id,
                'status' => 'COMPLETED',
                'amount' => $transaction->amount,
            ],
        ];
    }

    /**
     * Processa pagamento manual
     */
    protected function processManualPayment(PaymentTransaction $transaction, array $paymentData, array $config): array
    {
        return [
            'status' => 'pending',
            'external_id' => 'manual_' . $transaction->id,
            'response' => [
                'type' => 'manual',
                'instructions' => 'Aguardando confirmação manual do pagamento',
            ],
        ];
    }

    /**
     * Processa reembolso
     */
    public function processRefund(PaymentTransaction $transaction, float $amount = null, string $reason = null): PaymentTransaction
    {
        $refundAmount = $amount ?? $transaction->amount;

        if ($refundAmount > $transaction->amount) {
            throw new \Exception('Valor do reembolso não pode ser maior que o valor da transação');
        }

        $refundTransaction = PaymentTransaction::create([
            'order_id' => $transaction->order_id,
            'gateway' => $transaction->gateway,
            'status' => 'pending',
            'type' => 'refund',
            'amount' => $refundAmount,
            'currency' => $transaction->currency,
            'external_id' => $transaction->external_id,
            'meta_data' => [
                'original_transaction_id' => $transaction->transaction_id,
                'refund_reason' => $reason,
            ],
        ]);

        try {
            $result = $this->processRefundWithGateway($refundTransaction, $transaction);

            $refundTransaction->update([
                'status' => $result['status'],
                'gateway_response' => $result['response'] ?? null,
                'processed_at' => now(),
            ]);

            // Atualiza a transação original se foi reembolso total
            if ($refundAmount >= $transaction->amount) {
                $transaction->updateStatus('refunded');
            } else {
                $transaction->updateStatus('partially_refunded');
            }

            Log::info('Reembolso processado', [
                'refund_transaction_id' => $refundTransaction->transaction_id,
                'original_transaction_id' => $transaction->transaction_id,
                'amount' => $refundAmount,
                'status' => $result['status'],
            ]);

        } catch (\Exception $e) {
            $refundTransaction->update([
                'status' => 'failed',
                'notes' => $e->getMessage(),
                'processed_at' => now(),
            ]);

            Log::error('Erro no processamento do reembolso', [
                'refund_transaction_id' => $refundTransaction->transaction_id,
                'original_transaction_id' => $transaction->transaction_id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }

        return $refundTransaction;
    }

    /**
     * Processa reembolso com gateway específico
     */
    protected function processRefundWithGateway(PaymentTransaction $refundTransaction, PaymentTransaction $originalTransaction): array
    {
        $gateway = $refundTransaction->gateway;

        return match ($gateway) {
            'stripe' => $this->processStripeRefund($refundTransaction, $originalTransaction),
            'mercadopago' => $this->processMercadoPagoRefund($refundTransaction, $originalTransaction),
            'pagseguro' => $this->processPagSeguroRefund($refundTransaction, $originalTransaction),
            'paypal' => $this->processPayPalRefund($refundTransaction, $originalTransaction),
            default => throw new \Exception("Reembolso não suportado para gateway '{$gateway}'"),
        };
    }

    /**
     * Processa reembolso com Stripe
     */
    protected function processStripeRefund(PaymentTransaction $refundTransaction, PaymentTransaction $originalTransaction): array
    {
        // Implementação do reembolso Stripe
        return [
            'status' => 'completed',
            'response' => [
                'id' => 'stripe_refund_' . $refundTransaction->id,
                'status' => 'succeeded',
                'amount' => $refundTransaction->amount * 100,
            ],
        ];
    }

    /**
     * Processa reembolso com Mercado Pago
     */
    protected function processMercadoPagoRefund(PaymentTransaction $refundTransaction, PaymentTransaction $originalTransaction): array
    {
        // Implementação do reembolso Mercado Pago
        return [
            'status' => 'completed',
            'response' => [
                'id' => 'mp_refund_' . $refundTransaction->id,
                'status' => 'approved',
                'amount' => $refundTransaction->amount,
            ],
        ];
    }

    /**
     * Processa reembolso com PagSeguro
     */
    protected function processPagSeguroRefund(PaymentTransaction $refundTransaction, PaymentTransaction $originalTransaction): array
    {
        // Implementação do reembolso PagSeguro
        return [
            'status' => 'completed',
            'response' => [
                'code' => 'ps_refund_' . $refundTransaction->id,
                'status' => 'REFUNDED',
                'grossAmount' => $refundTransaction->amount,
            ],
        ];
    }

    /**
     * Processa reembolso com PayPal
     */
    protected function processPayPalRefund(PaymentTransaction $refundTransaction, PaymentTransaction $originalTransaction): array
    {
        // Implementação do reembolso PayPal
        return [
            'status' => 'completed',
            'response' => [
                'id' => 'pp_refund_' . $refundTransaction->id,
                'status' => 'COMPLETED',
                'amount' => $refundTransaction->amount,
            ],
        ];
    }

    /**
     * Obtém o gateway padrão
     */
    public function getDefaultGateway(): string
    {
        $config = PaymentGatewayConfig::where('key', 'default_gateway')
            ->where('environment', 'production')
            ->first();

        return $config ? $config->converted_value : 'manual';
    }

    /**
     * Define o gateway padrão
     */
    public function setDefaultGateway(string $gateway): void
    {
        PaymentGatewayConfig::updateOrCreate(
            [
                'gateway' => 'system',
                'environment' => 'production',
                'key' => 'default_gateway',
            ],
            [
                'value' => $gateway,
                'type' => 'string',
                'description' => 'Gateway de pagamento padrão',
                'is_public' => true,
                'is_required' => true,
            ]
        );
    }

    /**
     * Obtém gateways disponíveis
     */
    public function getAvailableGateways(): array
    {
        return PaymentTransaction::GATEWAYS;
    }

    /**
     * Verifica se um gateway está configurado
     */
    public function isGatewayConfigured(string $gateway, string $environment = 'production'): bool
    {
        $missingConfigs = PaymentGatewayConfig::getMissingRequiredConfigs($gateway, $environment);
        return empty($missingConfigs);
    }

    /**
     * Valida se um gateway está configurado
     */
    protected function validateGateway(string $gateway, string $environment = 'production'): void
    {
        if (!$this->isGatewayConfigured($gateway, $environment)) {
            $missingConfigs = PaymentGatewayConfig::getMissingRequiredConfigs($gateway, $environment);
            throw new \Exception("Gateway '{$gateway}' não está configurado. Configurações faltando: " . implode(', ', $missingConfigs));
        }
    }

    /**
     * Obtém configurações de um gateway
     */
    public function getGatewayConfig(string $gateway, string $environment = 'production'): array
    {
        return PaymentGatewayConfig::getGatewayConfig($gateway, $environment);
    }

    /**
     * Define configurações de um gateway
     */
    public function setGatewayConfig(string $gateway, string $environment, array $config): void
    {
        PaymentGatewayConfig::setGatewayConfig($gateway, $environment, $config);
    }

    /**
     * Obtém transações de um pedido
     */
    public function getOrderTransactions(int $orderId): \Illuminate\Database\Eloquent\Collection
    {
        return PaymentTransaction::where('order_id', $orderId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Obtém estatísticas de pagamentos
     */
    public function getPaymentStats(int $days = 30): array
    {
        $transactions = PaymentTransaction::where('created_at', '>=', now()->subDays($days))->get();

        return [
            'total_transactions' => $transactions->count(),
            'successful_transactions' => $transactions->where('status', 'completed')->count(),
            'failed_transactions' => $transactions->where('status', 'failed')->count(),
            'pending_transactions' => $transactions->where('status', 'pending')->count(),
            'total_volume' => $transactions->where('status', 'completed')->sum('amount'),
            'average_transaction_value' => $transactions->where('status', 'completed')->avg('amount'),
            'transactions_by_gateway' => $transactions->groupBy('gateway')->map->count(),
            'transactions_by_status' => $transactions->groupBy('status')->map->count(),
            'transactions_by_type' => $transactions->groupBy('type')->map->count(),
            'daily_transactions' => $transactions->groupBy(fn($t) => $t->created_at->format('Y-m-d'))
                ->map(function ($dayTransactions) {
                    return [
                        'count' => $dayTransactions->count(),
                        'volume' => $dayTransactions->sum('amount'),
                        'successful' => $dayTransactions->where('status', 'completed')->count(),
                    ];
                })
                ->sortKeysDesc(),
        ];
    }

    /**
     * Processa webhook de gateway
     */
    public function processWebhook(string $gateway, array $webhookData): void
    {
        Log::info('Webhook recebido', [
            'gateway' => $gateway,
            'data' => $webhookData,
        ]);

        // Processa o webhook baseado no gateway
        switch ($gateway) {
            case 'stripe':
                $this->processStripeWebhook($webhookData);
                break;
            case 'mercadopago':
                $this->processMercadoPagoWebhook($webhookData);
                break;
            case 'pagseguro':
                $this->processPagSeguroWebhook($webhookData);
                break;
            default:
                Log::warning('Gateway não suportado para webhook', ['gateway' => $gateway]);
        }
    }

    /**
     * Processa webhook do Stripe
     */
    protected function processStripeWebhook(array $webhookData): void
    {
        // Implementação do webhook Stripe
        Log::info('Stripe webhook processado', $webhookData);
    }

    /**
     * Processa webhook do Mercado Pago
     */
    protected function processMercadoPagoWebhook(array $webhookData): void
    {
        // Implementação do webhook Mercado Pago
        Log::info('Mercado Pago webhook processado', $webhookData);
    }

    /**
     * Processa webhook do PagSeguro
     */
    protected function processPagSeguroWebhook(array $webhookData): void
    {
        // Implementação do webhook PagSeguro
        Log::info('PagSeguro webhook processado', $webhookData);
    }

    /**
     * Obtém métodos de pagamento disponíveis
     */
    public function getAvailablePaymentMethods(string $gateway): array
    {
        return match ($gateway) {
            'stripe' => ['credit_card', 'debit_card', 'boleto', 'pix'],
            'mercadopago' => ['credit_card', 'debit_card', 'boleto', 'pix', 'wallet'],
            'pagseguro' => ['credit_card', 'boleto', 'pix'],
            'paypal' => ['paypal', 'credit_card'],
            'manual' => ['bank_transfer', 'cash', 'check'],
            default => [],
        };
    }

    /**
     * Calcula taxas de gateway
     */
    public function calculateGatewayFee(string $gateway, float $amount): float
    {
        $fees = [
            'stripe' => 0.029, // 2.9%
            'mercadopago' => 0.0399, // 3.99%
            'pagseguro' => 0.0399, // 3.99%
            'paypal' => 0.044, // 4.4%
        ];

        $feeRate = $fees[$gateway] ?? 0;
        return $amount * $feeRate;
    }

    /**
     * Verifica se um gateway suporta um método de pagamento
     */
    public function gatewaySupportsMethod(string $gateway, string $method): bool
    {
        $methods = $this->getAvailablePaymentMethods($gateway);
        return in_array($method, $methods);
    }
}