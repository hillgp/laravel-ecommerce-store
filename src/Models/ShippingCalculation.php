<?php

namespace LaravelEcommerceStore\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShippingCalculation extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
        'customer_id',
        'cart_id',
        'order_id',
        'postal_code_from',
        'postal_code_to',
        'total_weight',
        'total_value',
        'total_volume',
        'dimensions',
        'selected_method_id',
        'calculated_cost',
        'estimated_days',
        'tracking_code',
        'calculation_details',
        'api_response',
        'calculated_at',
    ];

    protected $casts = [
        'total_weight' => 'decimal:3',
        'total_value' => 'decimal:2',
        'total_volume' => 'decimal:3',
        'calculated_cost' => 'decimal:2',
        'dimensions' => 'array',
        'calculation_details' => 'array',
        'api_response' => 'array',
        'calculated_at' => 'datetime',
    ];

    /**
     * Get the customer that owns the calculation.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the cart that owns the calculation.
     */
    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    /**
     * Get the order that owns the calculation.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the selected shipping method.
     */
    public function shippingMethod(): BelongsTo
    {
        return $this->belongsTo(ShippingMethod::class, 'selected_method_id');
    }

    /**
     * Scope for session calculations.
     */
    public function scopeBySession($query, string $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }

    /**
     * Scope for customer calculations.
     */
    public function scopeByCustomer($query, int $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    /**
     * Scope for recent calculations.
     */
    public function scopeRecent($query, int $minutes = 30)
    {
        return $query->where('calculated_at', '>=', now()->subMinutes($minutes));
    }

    /**
     * Get formatted postal codes.
     */
    public function getFormattedPostalCodeFromAttribute(): string
    {
        return substr($this->postal_code_from, 0, 5) . '-' . substr($this->postal_code_from, 5);
    }

    public function getFormattedPostalCodeToAttribute(): string
    {
        return substr($this->postal_code_to, 0, 5) . '-' . substr($this->postal_code_to, 5);
    }

    /**
     * Get calculation summary.
     */
    public function getSummaryAttribute(): array
    {
        return [
            'from' => $this->formatted_postal_code_from,
            'to' => $this->formatted_postal_code_to,
            'weight' => $this->total_weight . 'kg',
            'value' => 'R$ ' . number_format($this->total_value, 2, ',', '.'),
            'method' => $this->shippingMethod?->name,
            'cost' => 'R$ ' . number_format($this->calculated_cost, 2, ',', '.'),
            'days' => $this->estimated_days . ' dia(s)',
        ];
    }

    /**
     * Check if calculation is still valid.
     */
    public function isValid(int $minutes = 30): bool
    {
        return $this->calculated_at->diffInMinutes(now()) <= $minutes;
    }

    /**
     * Get the tracking URL if available.
     */
    public function getTrackingUrl(): ?string
    {
        if (!$this->tracking_code || !$this->shippingMethod) {
            return null;
        }

        return $this->shippingMethod->carrier->getTrackingUrl($this->tracking_code);
    }
}