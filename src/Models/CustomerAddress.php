<?php

namespace LaravelEcommerceStore\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerAddress extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'type',
        'postal_code',
        'street',
        'number',
        'complement',
        'neighborhood',
        'city',
        'state',
        'country',
        'recipient_name',
        'recipient_phone',
        'is_default',
        'correios_data',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'correios_data' => 'array',
    ];

    /**
     * Get the customer that owns the address.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the formatted address.
     */
    public function getFormattedAddressAttribute(): string
    {
        $address = $this->street . ', ' . $this->number;

        if ($this->complement) {
            $address .= ', ' . $this->complement;
        }

        $address .= ' - ' . $this->neighborhood . ', ' . $this->city . ' - ' . $this->state;

        return $address;
    }

    /**
     * Get the formatted postal code.
     */
    public function getFormattedPostalCodeAttribute(): string
    {
        return substr($this->postal_code, 0, 5) . '-' . substr($this->postal_code, 5);
    }

    /**
     * Set the postal code attribute.
     */
    public function setPostalCodeAttribute($value)
    {
        $this->attributes['postal_code'] = preg_replace('/\D/', '', $value);
    }

    /**
     * Scope for default addresses.
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Scope for delivery addresses.
     */
    public function scopeDelivery($query)
    {
        return $query->where('type', 'delivery');
    }

    /**
     * Scope for billing addresses.
     */
    public function scopeBilling($query)
    {
        return $query->where('type', 'billing');
    }

    /**
     * Make this address the default for the customer.
     */
    public function makeDefault()
    {
        // Remove default flag from other addresses of the same customer and type
        $this->customer->addresses()
            ->where('type', $this->type)
            ->where('id', '!=', $this->id)
            ->update(['is_default' => false]);

        $this->update(['is_default' => true]);
    }

    /**
     * Get the full address for API calls.
     */
    public function getFullAddressForApiAttribute(): array
    {
        return [
            'postal_code' => $this->postal_code,
            'street' => $this->street,
            'number' => $this->number,
            'complement' => $this->complement,
            'neighborhood' => $this->neighborhood,
            'city' => $this->city,
            'state' => $this->state,
            'country' => $this->country,
        ];
    }
}