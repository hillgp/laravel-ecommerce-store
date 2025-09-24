<?php

namespace LaravelEcommerceStore\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShippingMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'carrier_id',
        'name',
        'code',
        'description',
        'base_cost',
        'cost_per_kg',
        'cost_per_km',
        'estimated_days_min',
        'estimated_days_max',
        'min_weight',
        'max_weight',
        'min_value',
        'max_value',
        'requires_insurance',
        'insurance_rate',
        'is_active',
        'supported_postal_codes',
        'excluded_postal_codes',
        'sort_order',
    ];

    protected $casts = [
        'base_cost' => 'decimal:2',
        'cost_per_kg' => 'decimal:2',
        'cost_per_km' => 'decimal:2',
        'min_weight' => 'decimal:3',
        'max_weight' => 'decimal:3',
        'min_value' => 'decimal:2',
        'max_value' => 'decimal:2',
        'requires_insurance' => 'boolean',
        'insurance_rate' => 'decimal:4',
        'is_active' => 'boolean',
        'supported_postal_codes' => 'array',
        'excluded_postal_codes' => 'array',
    ];

    /**
     * Get the carrier that owns the shipping method.
     */
    public function carrier(): BelongsTo
    {
        return $this->belongsTo(ShippingCarrier::class, 'carrier_id');
    }

    /**
     * Scope for active methods.
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
     * Check if method supports a postal code.
     */
    public function supportsPostalCode(string $postalCode): bool
    {
        $cleanPostalCode = preg_replace('/\D/', '', $postalCode);

        // Check excluded postal codes first
        if ($this->excluded_postal_codes) {
            foreach ($this->excluded_postal_codes as $excluded) {
                if ($this->matchesPostalCode($cleanPostalCode, $excluded)) {
                    return false;
                }
            }
        }

        // Check supported postal codes
        if ($this->supported_postal_codes) {
            foreach ($this->supported_postal_codes as $supported) {
                if ($this->matchesPostalCode($cleanPostalCode, $supported)) {
                    return true;
                }
            }
            return false; // If there are supported codes but none match, return false
        }

        return true; // If no specific codes, assume it supports all
    }

    /**
     * Check if postal code matches a pattern.
     */
    private function matchesPostalCode(string $postalCode, $pattern): bool
    {
        if (is_string($pattern)) {
            // Exact match or wildcard
            if ($pattern === $postalCode) {
                return true;
            }

            // Range pattern (e.g., "01310000-01310999")
            if (strpos($pattern, '-') !== false) {
                [$start, $end] = explode('-', $pattern);
                return $postalCode >= $start && $postalCode <= $end;
            }

            // Prefix pattern (e.g., "01310*")
            if (str_ends_with($pattern, '*')) {
                $prefix = rtrim($pattern, '*');
                return str_starts_with($postalCode, $prefix);
            }
        }

        return false;
    }

    /**
     * Check if method can handle the weight.
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
     * Check if method can handle the value.
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
     * Calculate shipping cost for given weight and distance.
     */
    public function calculateCost(float $weight, float $distance = 0, float $value = 0): array
    {
        $cost = $this->base_cost;

        // Add weight-based cost
        if ($this->cost_per_kg > 0) {
            $cost += ($weight * $this->cost_per_kg);
        }

        // Add distance-based cost
        if ($this->cost_per_km > 0 && $distance > 0) {
            $cost += ($distance * $this->cost_per_km);
        }

        // Add insurance cost
        $insuranceCost = 0;
        if ($this->requires_insurance && $value > 0) {
            $insuranceCost = $value * $this->insurance_rate;
            $cost += $insuranceCost;
        }

        return [
            'base_cost' => $this->base_cost,
            'weight_cost' => $weight * $this->cost_per_kg,
            'distance_cost' => $distance * $this->cost_per_km,
            'insurance_cost' => $insuranceCost,
            'total_cost' => $cost,
        ];
    }

    /**
     * Get estimated delivery days.
     */
    public function getEstimatedDays(): array
    {
        if ($this->estimated_days_min && $this->estimated_days_max) {
            return [
                'min' => $this->estimated_days_min,
                'max' => $this->estimated_days_max,
            ];
        }

        return ['min' => 1, 'max' => 3]; // Default fallback
    }

    /**
     * Get formatted estimated delivery text.
     */
    public function getEstimatedDeliveryText(): string
    {
        $days = $this->getEstimatedDays();

        if ($days['min'] === $days['max']) {
            return $days['min'] . ' dia' . ($days['min'] > 1 ? 's' : '');
        }

        return $days['min'] . ' a ' . $days['max'] . ' dias';
    }
}