<?php

namespace LaravelEcommerce\Store\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use LaravelEcommerce\Store\Models\Order;

class OrderDeliveredMail extends Mailable
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
        return $this->subject('Pedido Entregue #' . $this->order->order_number)
            ->view('store::emails.orders.delivered')
            ->with([
                'order' => $this->order,
                'customer' => $this->customer,
                'deliveryDate' => $this->order->delivered_at ?? now(),
                'canReview' => $this->canReviewProducts(),
            ]);
    }

    /**
     * Get the message subject.
     */
    public function getSubject(): string
    {
        return 'Pedido Entregue #' . $this->order->order_number;
    }

    /**
     * Get the message content type.
     */
    public function getContentType(): string
    {
        return 'text/html';
    }

    /**
     * Check if customer can review products.
     */
    protected function canReviewProducts(): bool
    {
        foreach ($this->order->items as $item) {
            if ($item->product->canBeReviewedBy($this->customer->id)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get reviewable products.
     */
    public function getReviewableProducts(): \Illuminate\Support\Collection
    {
        return $this->order->items->filter(function ($item) {
            return $item->product->canBeReviewedBy($this->customer->id);
        })->pluck('product');
    }

    /**
     * Get delivery feedback message.
     */
    public function getDeliveryFeedbackMessage(): string
    {
        $messages = [
            'Obrigado por escolher nossa loja! Sua satisfação é nossa prioridade.',
            'Esperamos que tenha gostado dos produtos. Sua opinião é muito importante para nós!',
            'Agradecemos pela confiança! Continue contando conosco para suas próximas compras.',
            'Sua satisfação nos motiva a melhorar sempre. Obrigado!',
        ];

        return $messages[array_rand($messages)];
    }
}