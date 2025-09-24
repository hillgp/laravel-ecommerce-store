<?php

namespace LaravelEcommerce\Store\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use LaravelEcommerce\Store\Models\Customer;

class CustomerRegistered
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $customer;
    public $ipAddress;
    public $userAgent;
    public $referrer;

    /**
     * Create a new event instance.
     */
    public function __construct(Customer $customer, $ipAddress = null, $userAgent = null, $referrer = null)
    {
        $this->customer = $customer;
        $this->ipAddress = $ipAddress ?? request()->ip();
        $this->userAgent = $userAgent ?? request()->userAgent();
        $this->referrer = $referrer ?? request()->header('referer');
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return ['customers'];
    }

    /**
     * Get the broadcast event name.
     */
    public function broadcastAs(): string
    {
        return 'customer.registered';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'customer_id' => $this->customer->id,
            'customer_name' => $this->customer->full_name,
            'customer_email' => $this->customer->email,
            'ip_address' => $this->ipAddress,
            'user_agent' => $this->userAgent,
            'referrer' => $this->referrer,
            'registered_at' => $this->customer->created_at->toISOString(),
        ];
    }

    /**
     * Get the event description for logging.
     */
    public function getDescription(): string
    {
        return "Novo cliente registrado: {$this->customer->full_name} ({$this->customer->email})";
    }

    /**
     * Get registration source information.
     */
    public function getRegistrationSource(): array
    {
        return [
            'ip_address' => $this->ipAddress,
            'user_agent' => $this->userAgent,
            'referrer' => $this->referrer,
            'source' => $this->determineSource(),
        ];
    }

    /**
     * Determine registration source.
     */
    protected function determineSource(): string
    {
        if (str_contains($this->referrer ?? '', 'facebook')) {
            return 'facebook';
        }

        if (str_contains($this->referrer ?? '', 'google')) {
            return 'google';
        }

        if (str_contains($this->referrer ?? '', 'instagram')) {
            return 'instagram';
        }

        if (str_contains($this->userAgent ?? '', 'Mobile')) {
            return 'mobile';
        }

        return 'website';
    }
}