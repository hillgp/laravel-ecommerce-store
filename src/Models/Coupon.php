<?php

namespace LaravelEcommerce\Store\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Coupon extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Nome da tabela
     */
    protected $table = 'coupons';

    /**
     * Campos preenchíveis
     */
    protected $fillable = [
        'code',
        'name',
        'description',
        'type',
        'value',
        'minimum_amount',
        'maximum_discount',
        'usage_limit',
        'usage_per_customer',
        'starts_at',
        'expires_at',
        'is_active',
        'applicable_categories',
        'applicable_products',
        'applicable_brands',
        'excluded_categories',
        'excluded_products',
        'excluded_brands',
        'first_purchase_only',
        'combine_with_others',
        'customer_groups',
        'used_count',
    ];

    /**
     * Campos que devem ser convertidos para tipos específicos
     */
    protected $casts = [
        'type' => 'string',
        'value' => 'decimal:2',
        'minimum_amount' => 'decimal:2',
        'maximum_discount' => 'decimal:2',
        'usage_limit' => 'integer',
        'usage_per_customer' => 'integer',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
        'first_purchase_only' => 'boolean',
        'combine_with_others' => 'boolean',
        'applicable_categories' => 'array',
        'applicable_products' => 'array',
        'applicable_brands' => 'array',
        'excluded_categories' => 'array',
        'excluded_products' => 'array',
        'excluded_brands' => 'array',
        'customer_groups' => 'array',
        'used_count' => 'integer',
    ];

    /**
     * Campos que devem ser ocultos na serialização
     */
    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * Tipos de cupom disponíveis
     */
    const TYPES = [
        'fixed' => 'Valor Fixo',
        'percentage' => 'Percentual',
        'free_shipping' => 'Frete Grátis',
    ];

    /**
     * Usos do cupom
     */
    public function usages(): HasMany
    {
        return $this->hasMany(CouponUsage::class, 'coupon_id');
    }

    /**
     * Scope para cupons ativos
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                    ->where(function ($q) {
                        $q->whereNull('starts_at')
                          ->orWhere('starts_at', '<=', now());
                    })
                    ->where(function ($q) {
                        $q->whereNull('expires_at')
                          ->orWhere('expires_at', '>=', now());
                    });
    }

    /**
     * Scope para cupons válidos
     */
    public function scopeValid($query)
    {
        return $query->where('is_active', true)
                    ->where('used_count', '<', $this->getRawOriginal('usage_limit'))
                    ->where(function ($q) {
                        $q->whereNull('starts_at')
                          ->orWhere('starts_at', '<=', now());
                    })
                    ->where(function ($q) {
                        $q->whereNull('expires_at')
                          ->orWhere('expires_at', '>=', now());
                    });
    }

    /**
     * Scope para cupons por código
     */
    public function scopeByCode($query, string $code)
    {
        return $query->where('code', strtoupper($code));
    }

    /**
     * Scope para cupons por tipo
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Verifica se o cupom está ativo
     */
    public function isActive(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = now();

        // Verifica data de início
        if ($this->starts_at && $this->starts_at->isFuture()) {
            return false;
        }

        // Verifica data de expiração
        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Verifica se o cupom pode ser usado
     */
    public function canBeUsed(): bool
    {
        return $this->isActive() && $this->hasUsageAvailable();
    }

    /**
     * Verifica se há uso disponível
     */
    public function hasUsageAvailable(): bool
    {
        // Verifica limite total
        if ($this->usage_limit && $this->used_count >= $this->usage_limit) {
            return false;
        }

        return true;
    }

    /**
     * Verifica se o cupom pode ser aplicado a um pedido
     */
    public function canBeAppliedToOrder(array $orderData, int $customerId): bool
    {
        if (!$this->canBeUsed()) {
            return false;
        }

        // Verifica valor mínimo
        if ($this->minimum_amount && $orderData['total'] < $this->minimum_amount) {
            return false;
        }

        // Verifica primeira compra
        if ($this->first_purchase_only) {
            $orderCount = Order::where('customer_id', $customerId)
                              ->where('status', '!=', 'cancelled')
                              ->count();
            if ($orderCount > 0) {
                return false;
            }
        }

        // Verifica grupos de clientes
        if ($this->customer_groups && !in_array($orderData['customer_group'] ?? null, $this->customer_groups)) {
            return false;
        }

        return true;
    }

    /**
     * Verifica se o cupom pode ser aplicado a produtos específicos
     */
    public function canBeAppliedToProducts(array $productIds): bool
    {
        // Se não há restrições específicas, pode ser aplicado
        if (empty($this->applicable_products) && empty($this->applicable_categories) && empty($this->applicable_brands)) {
            return true;
        }

        // Verifica produtos excluídos
        if ($this->excluded_products && array_intersect($productIds, $this->excluded_products)) {
            return false;
        }

        // Verifica categorias excluídas
        if ($this->excluded_categories) {
            $productsInExcludedCategories = Product::whereIn('id', $productIds)
                                                 ->whereHas('categories', function ($query) {
                                                     $query->whereIn('categories.id', $this->excluded_categories);
                                                 })
                                                 ->exists();
            if ($productsInExcludedCategories) {
                return false;
            }
        }

        // Verifica marcas excluídas
        if ($this->excluded_brands) {
            $productsInExcludedBrands = Product::whereIn('id', $productIds)
                                              ->whereIn('brand_id', $this->excluded_brands)
                                              ->exists();
            if ($productsInExcludedBrands) {
                return false;
            }
        }

        // Se há produtos aplicáveis específicos
        if ($this->applicable_products) {
            return !empty(array_intersect($productIds, $this->applicable_products));
        }

        // Se há categorias aplicáveis
        if ($this->applicable_categories) {
            $productsInApplicableCategories = Product::whereIn('id', $productIds)
                                                   ->whereHas('categories', function ($query) {
                                                       $query->whereIn('categories.id', $this->applicable_categories);
                                                   })
                                                   ->exists();
            return $productsInApplicableCategories;
        }

        // Se há marcas aplicáveis
        if ($this->applicable_brands) {
            $productsInApplicableBrands = Product::whereIn('id', $productIds)
                                                ->whereIn('brand_id', $this->applicable_brands)
                                                ->exists();
            return $productsInApplicableBrands;
        }

        return true;
    }

    /**
     * Calcula o desconto para um pedido
     */
    public function calculateDiscount(array $orderData): array
    {
        $subtotal = $orderData['subtotal'];
        $shipping = $orderData['shipping'] ?? 0;
        $total = $subtotal + $shipping;

        switch ($this->type) {
            case 'fixed':
                $discount = min($this->value, $total);
                break;

            case 'percentage':
                $discount = ($subtotal * $this->value) / 100;
                if ($this->maximum_discount) {
                    $discount = min($discount, $this->maximum_discount);
                }
                break;

            case 'free_shipping':
                $discount = $shipping;
                break;

            default:
                $discount = 0;
        }

        return [
            'discount' => round($discount, 2),
            'type' => $this->type,
            'description' => $this->getDiscountDescription(),
        ];
    }

    /**
     * Obtém a descrição do desconto
     */
    public function getDiscountDescription(): string
    {
        switch ($this->type) {
            case 'fixed':
                return "Desconto de R$ " . number_format($this->value, 2, ',', '.');
            case 'percentage':
                return "Desconto de {$this->value}%";
            case 'free_shipping':
                return "Frete Grátis";
            default:
                return "Desconto";
        }
    }

    /**
     * Registra o uso do cupom
     */
    public function recordUsage(int $customerId, int $orderId, float $discountAmount): void
    {
        $this->usages()->create([
            'customer_id' => $customerId,
            'order_id' => $orderId,
            'coupon_code' => $this->code,
            'discount_amount' => $discountAmount,
            'used_at' => now(),
        ]);

        $this->increment('used_count');
    }

    /**
     * Verifica se o cliente pode usar o cupom
     */
    public function canCustomerUse(int $customerId): bool
    {
        if (!$this->canBeUsed()) {
            return false;
        }

        // Verifica uso por cliente
        $customerUsageCount = $this->usages()
                                  ->where('customer_id', $customerId)
                                  ->count();

        return $customerUsageCount < $this->usage_per_customer;
    }

    /**
     * Obtém estatísticas do cupom
     */
    public function getStatsAttribute(): array
    {
        $totalUsages = $this->usages()->count();
        $uniqueCustomers = $this->usages()->distinct('customer_id')->count();
        $totalDiscount = $this->usages()->sum('discount_amount');

        return [
            'total_usages' => $totalUsages,
            'unique_customers' => $uniqueCustomers,
            'total_discount' => $totalDiscount,
            'usage_limit' => $this->usage_limit,
            'remaining_usage' => $this->usage_limit ? max(0, $this->usage_limit - $totalUsages) : null,
            'is_active' => $this->isActive(),
            'can_be_used' => $this->canBeUsed(),
        ];
    }

    /**
     * Obtém informações para exibição
     */
    public function getDisplayInfoAttribute(): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'type' => $this->type,
            'type_label' => self::TYPES[$this->type] ?? $this->type,
            'value' => $this->value,
            'discount_description' => $this->getDiscountDescription(),
            'minimum_amount' => $this->minimum_amount,
            'maximum_discount' => $this->maximum_discount,
            'usage_limit' => $this->usage_limit,
            'usage_per_customer' => $this->usage_per_customer,
            'starts_at' => $this->starts_at,
            'expires_at' => $this->expires_at,
            'is_active' => $this->is_active,
            'first_purchase_only' => $this->first_purchase_only,
            'combine_with_others' => $this->combine_with_others,
            'used_count' => $this->used_count,
            'stats' => $this->stats,
            'formatted_starts_at' => $this->starts_at?->format('d/m/Y H:i'),
            'formatted_expires_at' => $this->expires_at?->format('d/m/Y H:i'),
            'formatted_created_at' => $this->created_at->format('d/m/Y H:i'),
        ];
    }

    /**
     * Ativa o cupom
     */
    public function activate(): void
    {
        $this->update(['is_active' => true]);
    }

    /**
     * Desativa o cupom
     */
    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }

    /**
     * Verifica se o cupom expirou
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Verifica se o cupom ainda não começou
     */
    public function isNotStarted(): bool
    {
        return $this->starts_at && $this->starts_at->isFuture();
    }
}