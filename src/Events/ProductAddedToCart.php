<?php

namespace LaravelEcommerce\Store\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use LaravelEcommerce\Store\Models\Product;
use LaravelEcommerce\Store\Models\Cart;
use LaravelEcommerce\Store\Models\Customer;

class ProductAddedToCart
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $product;
    public $cart;
    public $customer;
    public $quantity;
    public $variantId;
    public $options;

    /**
     * Create a new event instance.
     */
    public function __construct(Product $product, Cart $cart, Customer $customer = null, int $quantity = 1, int $variantId = null, array $options = [])
    {
        $this->product = $product;
        $this->cart = $cart;
        $this->customer = $customer;
        $this->quantity = $quantity;
        $this->variantId = $variantId;
        $this->options = $options;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return ['cart'];
    }

    /**
     * Get the broadcast event name.
     */
    public function broadcastAs(): string
    {
        return 'product.added-to-cart';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'product_slug' => $this->product->slug,
            'product_image' => $this->product->image_url,
            'cart_id' => $this->cart->id,
            'customer_id' => $this->customer?->id,
            'quantity' => $this->quantity,
            'variant_id' => $this->variantId,
            'options' => $this->options,
            'cart_item_count' => $this->cart->item_count,
            'cart_total' => $this->cart->total_amount,
            'added_at' => now()->toISOString(),
        ];
    }

    /**
     * Determine if this event should be broadcast.
     */
    public function shouldBroadcast(): bool
    {
        // Broadcast only for authenticated customers
        return $this->customer !== null;
    }

    /**
     * Get the event description for logging.
     */
    public function getDescription(): string
    {
        $description = "Produto '{$this->product->name}' adicionado ao carrinho";

        if ($this->customer) {
            $description .= " pelo cliente '{$this->customer->full_name}'";
        }

        if ($this->quantity > 1) {
            $description .= " (quantidade: {$this->quantity})";
        }

        if ($this->variantId) {
            $description .= " (variante: {$this->variantId})";
        }

        return $description;
    }
}