<?php

namespace LaravelEcommerce\Store\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use LaravelEcommerce\Store\Models\Product;

class ProductViewed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $product;
    public $customer;
    public $ipAddress;
    public $userAgent;
    public $referrer;

    /**
     * Create a new event instance.
     */
    public function __construct(Product $product, $customer = null, $ipAddress = null, $userAgent = null, $referrer = null)
    {
        $this->product = $product;
        $this->customer = $customer;
        $this->ipAddress = $ipAddress ?? request()->ip();
        $this->userAgent = $userAgent ?? request()->userAgent();
        $this->referrer = $referrer ?? request()->header('referer');

        // Increment product view count
        $product->incrementViewCount();
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return ['products'];
    }

    /**
     * Get the broadcast event name.
     */
    public function broadcastAs(): string
    {
        return 'product.viewed';
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
            'customer_id' => $this->customer?->id,
            'ip_address' => $this->ipAddress,
            'user_agent' => $this->userAgent,
            'referrer' => $this->referrer,
            'viewed_at' => now()->toISOString(),
            'view_count' => $this->product->fresh()->view_count,
        ];
    }

    /**
     * Determine if this event should be broadcast.
     */
    public function shouldBroadcast(): bool
    {
        // Only broadcast for authenticated customers or specific conditions
        return $this->customer !== null;
    }
}