<?php

namespace LaravelEcommerce\Store\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Nome da tabela
     */
    protected $table = 'orders';

    /**
     * Campos preenchíveis
     */
    protected $fillable = [
        'order_number',
        'customer_id',
        'status',
        'payment_status',
        'shipping_status',
        'subtotal',
        'discount_amount',
        'shipping_cost',
        'tax_amount',
        'total',
        'currency',
        'payment_method',
        'payment_gateway',
        'transaction_id',
        'payment_data',
        'shipping_method',
        'tracking_number',
        'tracking_url',
        'shipping_data',
        'billing_address_id',
        'shipping_address_id',
        'notes',
        'internal_notes',
        'coupon_code',
        'meta_data',
        'confirmed_at',
        'shipped_at',
        'delivered_at',
        'cancelled_at',
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
        'payment_data' => 'array',
        'shipping_data' => 'array',
        'meta_data' => 'array',
        'confirmed_at' => 'datetime',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    /**
     * Campos que devem ser ocultos na serialização
     */
    protected $hidden = [
        'payment_data',
        'shipping_data',
        'meta_data',
        'internal_notes',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * Status possíveis do pedido
     */
    const STATUSES = [
        'pending' => 'Pendente',
        'confirmed' => 'Confirmado',
        'processing' => 'Processando',
        'shipped' => 'Enviado',
        'delivered' => 'Entregue',
        'cancelled' => 'Cancelado',
        'refunded' => 'Reembolsado',
        'returned' => 'Devolvido',
    ];

    /**
     * Status possíveis do pagamento
     */
    const PAYMENT_STATUSES = [
        'pending' => 'Pendente',
        'processing' => 'Processando',
        'paid' => 'Pago',
        'failed' => 'Falhou',
        'cancelled' => 'Cancelado',
        'refunded' => 'Reembolsado',
        'partially_refunded' => 'Parcialmente Reembolsado',
    ];

    /**
     * Status possíveis do envio
     */
    const SHIPPING_STATUSES = [
        'not_shipped' => 'Não Enviado',
        'preparing' => 'Preparando',
        'shipped' => 'Enviado',
        'in_transit' => 'Em Trânsito',
        'out_for_delivery' => 'Saiu para Entrega',
        'delivered' => 'Entregue',
        'failed_delivery' => 'Falha na Entrega',
        'returned' => 'Devolvido',
    ];

    /**
     * Boot do modelo
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            if (empty($order->order_number)) {
                $order->order_number = self::generateOrderNumber();
            }
        });
    }

    /**
     * Cliente proprietário do pedido
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    /**
     * Itens do pedido
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }

    /**
     * Endereço de cobrança
     */
    public function billingAddress(): BelongsTo
    {
        return $this->belongsTo(CustomerAddress::class, 'billing_address_id');
    }

    /**
     * Endereço de entrega
     */
    public function shippingAddress(): BelongsTo
    {
        return $this->belongsTo(CustomerAddress::class, 'shipping_address_id');
    }

    /**
     * Histórico de status
     */
    public function statusHistory(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class, 'order_id');
    }

    /**
     * Cupom usado no pedido
     */
    public function coupon()
    {
        return $this->belongsTo(Coupon::class, 'coupon_code', 'code');
    }

    /**
     * Scope para pedidos por status
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope para pedidos por status de pagamento
     */
    public function scopeByPaymentStatus($query, string $status)
    {
        return $query->where('payment_status', $status);
    }

    /**
     * Scope para pedidos por status de envio
     */
    public function scopeByShippingStatus($query, string $status)
    {
        return $query->where('shipping_status', $status);
    }

    /**
     * Scope para pedidos do cliente
     */
    public function scopeByCustomer($query, int $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    /**
     * Scope para pedidos recentes
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Scope para pedidos pendentes
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope para pedidos confirmados
     */
    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    /**
     * Scope para pedidos pagos
     */
    public function scopePaid($query)
    {
        return $query->where('payment_status', 'paid');
    }

    /**
     * Scope para pedidos enviados
     */
    public function scopeShipped($query)
    {
        return $query->where('shipping_status', 'shipped');
    }

    /**
     * Gera um número de pedido único
     */
    public static function generateOrderNumber(): string
    {
        do {
            $orderNumber = 'ORD-' . date('Ymd') . '-' . strtoupper(Str::random(6));
        } while (self::where('order_number', $orderNumber)->exists());

        return $orderNumber;
    }

    /**
     * Atualiza o status do pedido
     */
    public function updateStatus(string $status, string $notes = null, int $userId = null): void
    {
        $previousStatus = $this->status;

        $this->update([
            'status' => $status,
            'confirmed_at' => $status === 'confirmed' ? now() : $this->confirmed_at,
            'shipped_at' => $status === 'shipped' ? now() : $this->shipped_at,
            'delivered_at' => $status === 'delivered' ? now() : $this->delivered_at,
            'cancelled_at' => $status === 'cancelled' ? now() : $this->cancelled_at,
        ]);

        // Registra no histórico
        $this->statusHistory()->create([
            'status' => $status,
            'previous_status' => $previousStatus,
            'user_id' => $userId,
            'notes' => $notes,
            'meta_data' => [
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ],
        ]);
    }

    /**
     * Atualiza o status do pagamento
     */
    public function updatePaymentStatus(string $status, string $transactionId = null, array $paymentData = null): void
    {
        $this->update([
            'payment_status' => $status,
            'transaction_id' => $transactionId ?? $this->transaction_id,
            'payment_data' => $paymentData ?? $this->payment_data,
        ]);
    }

    /**
     * Atualiza o status do envio
     */
    public function updateShippingStatus(string $status, string $trackingNumber = null, string $trackingUrl = null): void
    {
        $this->update([
            'shipping_status' => $status,
            'tracking_number' => $trackingNumber ?? $this->tracking_number,
            'tracking_url' => $trackingUrl ?? $this->tracking_url,
        ]);
    }

    /**
     * Verifica se o pedido pode ser cancelado
     */
    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['pending', 'confirmed']) &&
               in_array($this->payment_status, ['pending', 'failed']);
    }

    /**
     * Verifica se o pedido pode ser reembolsado
     */
    public function canBeRefunded(): bool
    {
        return in_array($this->status, ['confirmed', 'processing', 'shipped']) &&
               $this->payment_status === 'paid';
    }

    /**
     * Verifica se o pedido pode ser enviado
     */
    public function canBeShipped(): bool
    {
        return in_array($this->status, ['confirmed', 'processing']) &&
               $this->payment_status === 'paid';
    }

    /**
     * Verifica se o pedido está pago
     */
    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    /**
     * Verifica se o pedido foi enviado
     */
    public function isShipped(): bool
    {
        return in_array($this->shipping_status, ['shipped', 'in_transit', 'out_for_delivery']);
    }

    /**
     * Verifica se o pedido foi entregue
     */
    public function isDelivered(): bool
    {
        return $this->shipping_status === 'delivered';
    }

    /**
     * Verifica se o pedido foi cancelado
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * Obtém o status formatado
     */
    public function getFormattedStatusAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /**
     * Obtém o status de pagamento formatado
     */
    public function getFormattedPaymentStatusAttribute(): string
    {
        return self::PAYMENT_STATUSES[$this->payment_status] ?? $this->payment_status;
    }

    /**
     * Obtém o status de envio formatado
     */
    public function getFormattedShippingStatusAttribute(): string
    {
        return self::SHIPPING_STATUSES[$this->shipping_status] ?? $this->shipping_status;
    }

    /**
     * Obtém o total formatado
     */
    public function getFormattedTotalAttribute(): string
    {
        return $this->currency . ' ' . number_format($this->total, 2, ',', '.');
    }

    /**
     * Obtém o subtotal formatado
     */
    public function getFormattedSubtotalAttribute(): string
    {
        return $this->currency . ' ' . number_format($this->subtotal, 2, ',', '.');
    }

    /**
     * Obtém o valor do desconto formatado
     */
    public function getFormattedDiscountAttribute(): string
    {
        return $this->currency . ' ' . number_format($this->discount_amount, 2, ',', '.');
    }

    /**
     * Obtém o valor do frete formatado
     */
    public function getFormattedShippingCostAttribute(): string
    {
        return $this->currency . ' ' . number_format($this->shipping_cost, 2, ',', '.');
    }

    /**
     * Obtém o valor dos impostos formatado
     */
    public function getFormattedTaxAttribute(): string
    {
        return $this->currency . ' ' . number_format($this->tax_amount, 2, ',', '.');
    }

    /**
     * Obtém estatísticas do pedido
     */
    public function getStatsAttribute(): array
    {
        return [
            'status' => $this->status,
            'payment_status' => $this->payment_status,
            'shipping_status' => $this->shipping_status,
            'is_paid' => $this->isPaid(),
            'is_shipped' => $this->isShipped(),
            'is_delivered' => $this->isDelivered(),
            'is_cancelled' => $this->isCancelled(),
            'can_be_cancelled' => $this->canBeCancelled(),
            'can_be_refunded' => $this->canBeRefunded(),
            'can_be_shipped' => $this->canBeShipped(),
            'items_count' => $this->items()->count(),
            'total_quantity' => $this->items()->sum('quantity'),
        ];
    }

    /**
     * Obtém informações de rastreamento
     */
    public function getTrackingInfoAttribute(): array
    {
        return [
            'tracking_number' => $this->tracking_number,
            'tracking_url' => $this->tracking_url,
            'shipping_status' => $this->shipping_status,
            'shipped_at' => $this->shipped_at,
            'delivered_at' => $this->delivered_at,
        ];
    }

    /**
     * Obtém informações de pagamento
     */
    public function getPaymentInfoAttribute(): array
    {
        return [
            'payment_method' => $this->payment_method,
            'payment_gateway' => $this->payment_gateway,
            'transaction_id' => $this->transaction_id,
            'payment_status' => $this->payment_status,
            'payment_data' => $this->payment_data,
        ];
    }

    /**
     * Obtém informações de envio
     */
    public function getShippingInfoAttribute(): array
    {
        return [
            'shipping_method' => $this->shipping_method,
            'tracking_number' => $this->tracking_number,
            'tracking_url' => $this->tracking_url,
            'shipping_status' => $this->shipping_status,
            'shipping_data' => $this->shipping_data,
        ];
    }

    /**
     * Calcula o tempo de processamento do pedido
     */
    public function getProcessingTimeAttribute(): ?int
    {
        if (!$this->confirmed_at) {
            return null;
        }

        $endTime = $this->shipped_at ?? now();
        return $this->confirmed_at->diffInHours($endTime);
    }

    /**
     * Calcula o tempo de entrega do pedido
     */
    public function getDeliveryTimeAttribute(): ?int
    {
        if (!$this->shipped_at || !$this->delivered_at) {
            return null;
        }

        return $this->shipped_at->diffInHours($this->delivered_at);
    }
}