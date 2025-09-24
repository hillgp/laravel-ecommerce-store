<?php

namespace LaravelEcommerce\Store\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use LaravelEcommerce\Store\Models\Order;

class OrderConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $order;
    public $customer;

    /**
     * Create a new message instance.
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
        $this->customer = $order->customer;
    }

    /**
     * Build the message.
     */
    public function build(): self
    {
        return $this->subject('Confirmação do Pedido #' . $this->order->order_number)
            ->view('store::emails.orders.confirmation')
            ->with([
                'order' => $this->order,
                'customer' => $this->customer,
                'billingAddress' => $this->order->billing_address,
                'shippingAddress' => $this->order->shipping_address,
            ])
            ->attachData($this->generateOrderPdf(), 'pedido-' . $this->order->order_number . '.pdf', [
                'mime' => 'application/pdf',
            ]);
    }

    /**
     * Generate order PDF.
     */
    protected function generateOrderPdf(): string
    {
        // This would generate a PDF of the order
        // For now, return a simple text representation
        $content = "Pedido #{$this->order->order_number}\n";
        $content .= "Cliente: {$this->customer->full_name}\n";
        $content .= "Total: R$ " . number_format($this->order->total_amount, 2, ',', '.') . "\n";
        $content .= "Data: " . $this->order->created_at->format('d/m/Y H:i') . "\n";

        return $content;
    }

    /**
     * Get the message subject.
     */
    public function getSubject(): string
    {
        return 'Confirmação do Pedido #' . $this->order->order_number;
    }

    /**
     * Get the message content type.
     */
    public function getContentType(): string
    {
        return 'text/html';
    }
}