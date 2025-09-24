<?php

namespace LaravelEcommerce\Store\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use LaravelEcommerce\Store\Models\Order;
use LaravelEcommerce\Store\Events\OrderProcessed;
use LaravelEcommerce\Store\Services\OrderService;

class ProcessOrderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $order;
    protected $orderService;

    /**
     * Create a new job instance.
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
        $this->orderService = app(OrderService::class);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->info("Processando pedido #{$this->order->order_number}");

        try {
            // Update order status to processing
            $this->order->updateStatus('processing', 'Pedido sendo processado');

            // Reserve inventory
            $this->reserveInventory();

            // Calculate shipping
            $this->calculateShipping();

            // Apply discounts
            $this->applyDiscounts();

            // Process payment
            $this->processPayment();

            // Send confirmation email
            $this->sendConfirmationEmail();

            // Update order status to confirmed
            $this->order->updateStatus('confirmed', 'Pedido confirmado e pronto para envio');

            // Trigger event
            event(new OrderProcessed($this->order));

            $this->info("Pedido #{$this->order->order_number} processado com sucesso");

        } catch (\Exception $e) {
            $this->error("Erro ao processar pedido #{$this->order->order_number}: " . $e->getMessage());

            // Update order status to failed
            $this->order->updateStatus('failed', 'Erro no processamento: ' . $e->getMessage());

            // Re-throw exception to mark job as failed
            throw $e;
        }
    }

    /**
     * Reserve inventory for order items.
     */
    protected function reserveInventory(): void
    {
        $this->info("Reservando estoque para pedido #{$this->order->order_number}");

        foreach ($this->order->items as $item) {
            if ($item->product->track_inventory) {
                $item->product->reserveStock($item->quantity, $this->order->id);
            }
        }

        $this->info("Estoque reservado com sucesso");
    }

    /**
     * Calculate shipping costs.
     */
    protected function calculateShipping(): void
    {
        $this->info("Calculando frete para pedido #{$this->order->order_number}");

        $shippingService = app(\LaravelEcommerce\Store\Services\ShippingService::class);
        $shippingAmount = $shippingService->calculateShipping(
            $this->order->shipping_address,
            $this->order->items,
            $this->order->total_amount
        );

        $this->order->update(['shipping_amount' => $shippingAmount]);

        $this->info("Frete calculado: R$ " . number_format($shippingAmount, 2, ',', '.'));
    }

    /**
     * Apply discounts and coupons.
     */
    protected function applyDiscounts(): void
    {
        $this->info("Aplicando descontos para pedido #{$this->order->order_number}");

        if ($this->order->coupon_code) {
            $coupon = $this->order->coupon;
            if ($coupon) {
                $discountAmount = $coupon->calculateDiscount($this->order->subtotal);
                $this->order->update(['discount_amount' => $discountAmount]);
                $this->info("Desconto aplicado: R$ " . number_format($discountAmount, 2, ',', '.'));
            }
        }
    }

    /**
     * Process payment.
     */
    protected function processPayment(): void
    {
        $this->info("Processando pagamento para pedido #{$this->order->order_number}");

        $paymentService = app(\LaravelEcommerce\Store\Services\PaymentService::class);

        try {
            $paymentResult = $paymentService->processPayment($this->order);

            if ($paymentResult['success']) {
                $this->order->updatePaymentStatus('paid', 'Pagamento processado com sucesso');
                $this->info("Pagamento processado com sucesso");
            } else {
                $this->order->updatePaymentStatus('failed', 'Falha no processamento do pagamento: ' . $paymentResult['message']);
                throw new \Exception('Falha no pagamento: ' . $paymentResult['message']);
            }
        } catch (\Exception $e) {
            $this->order->updatePaymentStatus('failed', 'Erro no pagamento: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Send order confirmation email.
     */
    protected function sendConfirmationEmail(): void
    {
        $this->info("Enviando email de confirmação para pedido #{$this->order->order_number}");

        $notificationService = app(\LaravelEcommerce\Store\Services\NotificationService::class);
        $notificationService->sendOrderConfirmation($this->order);

        $this->info("Email de confirmação enviado");
    }

    /**
     * Handle job failure.
     */
    public function failed(\Throwable $exception): void
    {
        $this->error("Job falhou para pedido #{$this->order->order_number}: " . $exception->getMessage());

        // Log failure
        \Log::error('ProcessOrderJob failed', [
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        // Notify admin
        $notificationService = app(\LaravelEcommerce\Store\Services\NotificationService::class);
        $notificationService->notifyAdminOrderFailure($this->order, $exception);
    }

    /**
     * Log info message.
     */
    protected function info(string $message): void
    {
        \Log::info($message);
        $this->comment($message);
    }

    /**
     * Log error message.
     */
    protected function error(string $message): void
    {
        \Log::error($message);
        $this->error($message);
    }

    /**
     * Output comment to console.
     */
    protected function comment(string $message): void
    {
        if (app()->runningInConsole()) {
            $this->output->writeln("<comment>{$message}</comment>");
        }
    }
}