<?php

namespace LaravelEcommerce\Store\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CouponUsage extends Model
{
    use HasFactory;

    /**
     * Nome da tabela
     */
    protected $table = 'coupon_usages';

    /**
     * Campos preenchíveis
     */
    protected $fillable = [
        'coupon_id',
        'customer_id',
        'order_id',
        'coupon_code',
        'discount_amount',
        'used_at',
    ];

    /**
     * Campos que devem ser convertidos para tipos específicos
     */
    protected $casts = [
        'coupon_id' => 'integer',
        'customer_id' => 'integer',
        'order_id' => 'integer',
        'discount_amount' => 'decimal:2',
        'used_at' => 'datetime',
    ];

    /**
     * Campos que devem ser ocultos na serialização
     */
    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    /**
     * Cupom utilizado
     */
    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class, 'coupon_id');
    }

    /**
     * Cliente que utilizou o cupom
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    /**
     * Pedido relacionado ao uso do cupom
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /**
     * Scope para usos por cupom
     */
    public function scopeByCoupon($query, int $couponId)
    {
        return $query->where('coupon_id', $couponId);
    }

    /**
     * Scope para usos por cliente
     */
    public function scopeByCustomer($query, int $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    /**
     * Scope para usos por pedido
     */
    public function scopeByOrder($query, int $orderId)
    {
        return $query->where('order_id', $orderId);
    }

    /**
     * Scope para usos por código do cupom
     */
    public function scopeByCouponCode($query, string $code)
    {
        return $query->where('coupon_code', $code);
    }

    /**
     * Scope para usos em um período
     */
    public function scopeInPeriod($query, string $startDate, string $endDate)
    {
        return $query->whereBetween('used_at', [$startDate, $endDate]);
    }

    /**
     * Obtém informações do cupom para exibição
     */
    public function getCouponInfoAttribute(): array
    {
        return [
            'id' => $this->coupon_id,
            'code' => $this->coupon_code,
            'name' => $this->coupon->name,
            'type' => $this->coupon->type,
            'value' => $this->coupon->value,
        ];
    }

    /**
     * Obtém informações do cliente para exibição
     */
    public function getCustomerInfoAttribute(): array
    {
        return [
            'id' => $this->customer_id,
            'name' => $this->customer->name,
            'email' => $this->customer->email,
            'avatar' => $this->customer->avatar_url,
        ];
    }

    /**
     * Obtém informações do pedido para exibição
     */
    public function getOrderInfoAttribute(): array
    {
        return [
            'id' => $this->order_id,
            'number' => $this->order->order_number ?? null,
            'total' => $this->order->total ?? null,
            'status' => $this->order->status ?? null,
        ];
    }

    /**
     * Obtém informações para exibição
     */
    public function getDisplayInfoAttribute(): array
    {
        return [
            'id' => $this->id,
            'coupon_id' => $this->coupon_id,
            'customer_id' => $this->customer_id,
            'order_id' => $this->order_id,
            'coupon_code' => $this->coupon_code,
            'discount_amount' => $this->discount_amount,
            'formatted_discount_amount' => 'R$ ' . number_format($this->discount_amount, 2, ',', '.'),
            'used_at' => $this->used_at,
            'formatted_used_at' => $this->used_at->format('d/m/Y H:i:s'),
            'coupon_info' => $this->coupon_info,
            'customer_info' => $this->customer_info,
            'order_info' => $this->order_info,
            'created_at' => $this->created_at,
            'formatted_created_at' => $this->created_at->format('d/m/Y H:i:s'),
        ];
    }

    /**
     * Verifica se o uso pode ser cancelado
     */
    public function canBeCancelled(): bool
    {
        // Pode ser cancelado se o pedido ainda não foi processado
        if ($this->order && in_array($this->order->status, ['completed', 'shipped', 'delivered'])) {
            return false;
        }

        return true;
    }

    /**
     * Cancela o uso do cupom
     */
    public function cancel(): bool
    {
        if (!$this->canBeCancelled()) {
            return false;
        }

        // Remove o uso
        $this->delete();

        // Decrementa o contador do cupom
        if ($this->coupon) {
            $this->coupon->decrement('used_count');
        }

        return true;
    }

    /**
     * Obtém estatísticas do uso
     */
    public function getStatsAttribute(): array
    {
        return [
            'discount_amount' => $this->discount_amount,
            'formatted_discount_amount' => $this->formatted_discount_amount,
            'can_be_cancelled' => $this->canBeCancelled(),
            'order_status' => $this->order->status ?? null,
        ];
    }
}