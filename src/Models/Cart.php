<?php

namespace LaravelEcommerce\Store\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Session;

class Cart extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Nome da tabela
     */
    protected $table = 'carts';

    /**
     * Campos preenchíveis
     */
    protected $fillable = [
        'session_id',
        'customer_id',
        'coupon_code',
        'subtotal',
        'discount_amount',
        'shipping_cost',
        'tax_amount',
        'total',
        'items_count',
        'meta_data',
        'last_activity',
    ];

    /**
     * Campos que devem ser convertidos para tipos específicos
     */
    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'items_count' => 'integer',
        'meta_data' => 'array',
        'last_activity' => 'datetime',
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
     * Boot do modelo
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($cart) {
            $cart->last_activity = now();
        });

        static::updating(function ($cart) {
            $cart->last_activity = now();
        });
    }

    /**
     * Cliente proprietário do carrinho
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    /**
     * Itens do carrinho
     */
    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class, 'cart_id');
    }

    /**
     * Cupom aplicado ao carrinho
     */
    public function coupon()
    {
        return $this->belongsTo(Coupon::class, 'coupon_code', 'code');
    }

    /**
     * Scope para carrinhos ativos
     */
    public function scopeActive($query)
    {
        return $query->where('items_count', '>', 0);
    }

    /**
     * Scope para carrinhos de convidados
     */
    public function scopeGuest($query)
    {
        return $query->whereNull('customer_id');
    }

    /**
     * Scope para carrinhos de clientes logados
     */
    public function scopeAuthenticated($query)
    {
        return $query->whereNotNull('customer_id');
    }

    /**
     * Scope para carrinhos recentes
     */
    public function scopeRecent($query, $minutes = 30)
    {
        return $query->where('last_activity', '>=', now()->subMinutes($minutes));
    }

    /**
     * Obtém ou cria um carrinho para o cliente/sessão atual
     */
    public static function getCurrentCart($customer = null): self
    {
        if ($customer) {
            $cart = self::where('customer_id', $customer->id)->first();

            if (!$cart) {
                $cart = self::create(['customer_id' => $customer->id]);
            }
        } else {
            $sessionId = Session::getId();
            $cart = self::where('session_id', $sessionId)->first();

            if (!$cart) {
                $cart = self::create(['session_id' => $sessionId]);
            }
        }

        return $cart;
    }

    /**
     * Adiciona um item ao carrinho
     */
    public function addItem(array $data): CartItem
    {
        // Verifica se o item já existe
        $existingItem = $this->items()
            ->where('product_id', $data['product_id'])
            ->where('product_variant_id', $data['product_variant_id'] ?? null)
            ->first();

        if ($existingItem) {
            $existingItem->update([
                'quantity' => $existingItem->quantity + $data['quantity'],
                'total' => ($existingItem->quantity + $data['quantity']) * $existingItem->price,
            ]);

            $this->updateTotals();
            return $existingItem;
        }

        // Cria novo item
        $item = $this->items()->create([
            'product_id' => $data['product_id'],
            'product_variant_id' => $data['product_variant_id'] ?? null,
            'quantity' => $data['quantity'],
            'price' => $data['price'],
            'total' => $data['quantity'] * $data['price'],
            'options' => $data['options'] ?? null,
            'meta_data' => $data['meta_data'] ?? null,
        ]);

        $this->updateTotals();
        return $item;
    }

    /**
     * Remove um item do carrinho
     */
    public function removeItem($itemId): bool
    {
        $item = $this->items()->find($itemId);

        if (!$item) {
            return false;
        }

        $item->delete();
        $this->updateTotals();

        return true;
    }

    /**
     * Atualiza a quantidade de um item
     */
    public function updateItemQuantity($itemId, int $quantity): bool
    {
        $item = $this->items()->find($itemId);

        if (!$item) {
            return false;
        }

        $item->update([
            'quantity' => $quantity,
            'total' => $quantity * $item->price,
        ]);

        $this->updateTotals();
        return true;
    }

    /**
     * Aplica um cupom ao carrinho
     */
    public function applyCoupon(string $couponCode): bool
    {
        $coupon = Coupon::where('code', $couponCode)
            ->where('is_active', true)
            ->where('valid_from', '<=', now())
            ->where(function ($query) {
                $query->where('valid_until', '>=', now())
                      ->orWhereNull('valid_until');
            })
            ->first();

        if (!$coupon) {
            return false;
        }

        // Verifica se o cupom pode ser usado
        if (!$coupon->canBeUsed($this->customer_id)) {
            return false;
        }

        $this->update([
            'coupon_code' => $couponCode,
        ]);

        $this->updateTotals();
        return true;
    }

    /**
     * Remove o cupom do carrinho
     */
    public function removeCoupon(): void
    {
        $this->update([
            'coupon_code' => null,
            'discount_amount' => 0,
        ]);

        $this->updateTotals();
    }

    /**
     * Atualiza os totais do carrinho
     */
    public function updateTotals(): void
    {
        $items = $this->items;

        $subtotal = $items->sum('total');
        $itemsCount = $items->sum('quantity');

        $discountAmount = 0;
        if ($this->coupon_code) {
            $coupon = $this->coupon;
            if ($coupon) {
                $discountAmount = $coupon->calculateDiscount($subtotal);
            }
        }

        $total = $subtotal - $discountAmount;

        $this->update([
            'subtotal' => $subtotal,
            'discount_amount' => $discountAmount,
            'total' => $total,
            'items_count' => $itemsCount,
        ]);
    }

    /**
     * Calcula o frete do carrinho
     */
    public function calculateShipping(array $address): float
    {
        // Implementação do cálculo de frete
        // Por enquanto retorna um valor fixo
        return 15.00;
    }

    /**
     * Calcula os impostos do carrinho
     */
    public function calculateTax(): float
    {
        // Implementação do cálculo de impostos
        // Por enquanto retorna 0
        return 0.00;
    }

    /**
     * Limpa o carrinho
     */
    public function clear(): void
    {
        $this->items()->delete();
        $this->update([
            'subtotal' => 0,
            'discount_amount' => 0,
            'shipping_cost' => 0,
            'tax_amount' => 0,
            'total' => 0,
            'items_count' => 0,
            'coupon_code' => null,
        ]);
    }

    /**
     * Verifica se o carrinho está vazio
     */
    public function isEmpty(): bool
    {
        return $this->items_count === 0;
    }

    /**
     * Verifica se o carrinho tem itens
     */
    public function hasItems(): bool
    {
        return $this->items_count > 0;
    }

    /**
     * Obtém o valor total formatado
     */
    public function getFormattedTotalAttribute(): string
    {
        return 'R$ ' . number_format($this->total, 2, ',', '.');
    }

    /**
     * Obtém o subtotal formatado
     */
    public function getFormattedSubtotalAttribute(): string
    {
        return 'R$ ' . number_format($this->subtotal, 2, ',', '.');
    }

    /**
     * Obtém o valor do desconto formatado
     */
    public function getFormattedDiscountAttribute(): string
    {
        return 'R$ ' . number_format($this->discount_amount, 2, ',', '.');
    }

    /**
     * Obtém estatísticas do carrinho
     */
    public function getStatsAttribute(): array
    {
        return [
            'items_count' => $this->items_count,
            'subtotal' => $this->subtotal,
            'discount_amount' => $this->discount_amount,
            'total' => $this->total,
            'is_empty' => $this->isEmpty(),
            'has_items' => $this->hasItems(),
            'has_coupon' => !empty($this->coupon_code),
            'coupon_code' => $this->coupon_code,
        ];
    }

    /**
     * Mescla carrinho de convidado com carrinho de cliente
     */
    public function mergeGuestCart(Cart $guestCart): void
    {
        foreach ($guestCart->items as $item) {
            $existingItem = $this->items()
                ->where('product_id', $item->product_id)
                ->where('product_variant_id', $item->product_variant_id)
                ->first();

            if ($existingItem) {
                $existingItem->update([
                    'quantity' => $existingItem->quantity + $item->quantity,
                    'total' => ($existingItem->quantity + $item->quantity) * $existingItem->price,
                ]);
            } else {
                $item->update(['cart_id' => $this->id]);
            }
        }

        $this->updateTotals();
        $guestCart->delete();
    }
}