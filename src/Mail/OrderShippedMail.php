<?php

namespace LaravelEcommerce\Store\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use LaravelEcommerce\Store\Models\Order;

class OrderShippedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $order;
    public $customer;
    public $trackingNumber;
    public $trackingUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(Order $order, string $trackingNumber = null, string $trackingUrl = null)
    {
        $this->order = $order;
        $this->customer = $order->customer;
        $this->trackingNumber = $trackingNumber ?? $order->tracking_number;
        $this->trackingUrl = $trackingUrl ?? $order->tracking_url;
    }

    /**
     * Build the message.
     */
    public function build(): self
    {
        return $this->subject('Pedido Enviado #' . $this->order->order_number)
            ->view('store::emails.orders.shipped')
            ->with([
                'order' => $this->order,
                'customer' => $this->customer,
                'trackingNumber' => $this->trackingNumber,
                'trackingUrl' => $this->trackingUrl,
                'shippingAddress' => $this->order->shipping_address,
            ]);
    }

    /**
     * Get the message subject.
     */
    public function getSubject(): string
    {
        return 'Pedido Enviado #' . $this->order->order_number;
    }

    /**
     * Get the message content type.
     */
    public function getContentType(): string
    {
        return 'text/html';
    }

    /**
     * Get the estimated delivery date.
     */
    public function getEstimatedDelivery(): string
    {
        $shippedAt = $this->order->shipped_at ?? now();

        // Estimate delivery based on shipping method
        $estimatedDays = $this->getEstimatedDeliveryDays();
        $estimatedDate = $shippedAt->copy()->addDays($estimatedDays);

        return $estimatedDate->format('d/m/Y');
    }

    /**
     * Get estimated delivery days based on shipping method.
     */
    protected function getEstimatedDeliveryDays(): int
    {
        $shippingMethod = $this->order->shipping_method ?? 'standard';

        return match ($shippingMethod) {
            'express' => 1,
            'priority' => 2,
            'standard' => 5,
            'economy' => 7,
            default => 5,
        };
    }
}