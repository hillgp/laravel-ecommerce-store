<?php

namespace LaravelEcommerceStore\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShippingCarrier extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'tracking_url',
        'is_active',
        'settings',
        'min_weight',
        'max_weight',
        'min_value',
        'max_value',
        'supported_regions',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'settings' => 'array',
        'supported_regions' => 'array',
        'min_weight' => 'decimal:3',
        'max_weight' => 'decimal:3',
        'min_value' => 'decimal:2',
        'max_value' => 'decimal:2',
    ];

    /**
     * Get the shipping methods for this carrier.
     */
    public function shippingMethods(): HasMany
    {
        return $this->hasMany(ShippingMethod::class, 'carrier_id');
    }

    /**
     * Get active shipping methods.
     */
    public function activeShippingMethods(): HasMany
    {
        return $this->shippingMethods()->where('is_active', true);
    }

    /**
     * Scope for active carriers.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for ordering by sort order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Check if carrier supports a postal code.
     */
    public function supportsPostalCode(string $postalCode): bool
    {
        // Remove formatting from postal code
        $cleanPostalCode = preg_replace('/\D/', '', $postalCode);

        // Check if there are specific supported regions
        if ($this->supported_regions) {
            foreach ($this->supported_regions as $region) {
                if (isset($region['type']) && isset($region['value'])) {
                    switch ($region['type']) {
                        case 'state':
                            if (substr($cleanPostalCode, 0, 2) === $region['value']) {
                                return true;
                            }
                            break;
                        case 'city':
                            // This would require more complex logic to match city codes
                            break;
                        case 'postal_code_range':
                            if (isset($region['start']) && isset($region['end'])) {
                                $code = intval($cleanPostalCode);
                                if ($code >= $region['start'] && $code <= $region['end']) {
                                    return true;
                                }
                            }
                            break;
                    }
                }
            }
        }

        return true; // If no specific regions, assume it supports all
    }

    /**
     * Check if carrier can handle the weight.
     */
    public function canHandleWeight(float $weight): bool
    {
        if ($this->min_weight && $weight < $this->min_weight) {
            return false;
        }

        if ($this->max_weight && $weight > $this->max_weight) {
            return false;
        }

        return true;
    }

    /**
     * Check if carrier can handle the value.
     */
    public function canHandleValue(float $value): bool
    {
        if ($this->min_value && $value < $this->min_value) {
            return false;
        }

        if ($this->max_value && $value > $this->max_value) {
            return false;
        }

        return true;
    }

    /**
     * Get tracking URL for a tracking code.
     */
    public function getTrackingUrl(string $trackingCode): ?string
    {
        if (!$this->tracking_url) {
            return null;
        }

        return str_replace(':tracking_code', $trackingCode, $this->tracking_url);
    }
}