<?php

namespace LaravelEcommerce\Store\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderItem extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Nome da tabela
     */
    protected $table = 'order_items';

    /**
     * Campos preenchíveis
     */
    protected $fillable = [
        'order_id',
        'product_id',
        'product_variant_id',
        'product_name',
        'product_sku',
        'quantity',
        'price',
        'total',
        'discount_amount',
        'tax_amount',
        'options',
        'attributes',
        'meta_data',
    ];

    /**
     * Campos que devem ser convertidos para tipos específicos
     */
    protected $casts = [
        'order_id' => 'integer',
        'product_id' => 'integer',
        'product_variant_id' => 'integer',
        'quantity' => 'integer',
        'price' => 'decimal:2',
        'total' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'options' => 'array',
        'attributes' => 'array',
        'meta_data' => 'array',
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
     * Pedido ao qual o item pertence
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /**
     * Produto do item
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Variação do produto (se aplicável)
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    /**
     * Obtém o preço unitário formatado
     */
    public function getFormattedPriceAttribute(): string
    {
        return $this->order->currency . ' ' . number_format($this->price, 2, ',', '.');
    }

    /**
     * Obtém o preço total formatado
     */
    public function getFormattedTotalAttribute(): string
    {
        return $this->order->currency . ' ' . number_format($this->total, 2, ',', '.');
    }

    /**
     * Obtém o valor do desconto formatado
     */
    public function getFormattedDiscountAttribute(): string
    {
        return $this->order->currency . ' ' . number_format($this->discount_amount, 2, ',', '.');
    }

    /**
     * Obtém o valor dos impostos formatado
     */
    public function getFormattedTaxAttribute(): string
    {
        return $this->order->currency . ' ' . number_format($this->tax_amount, 2, ',', '.');
    }

    /**
     * Obtém o preço com desconto
     */
    public function getDiscountedPriceAttribute(): float
    {
        return $this->price - $this->discount_amount;
    }

    /**
     * Obtém o preço com desconto formatado
     */
    public function getFormattedDiscountedPriceAttribute(): string
    {
        return $this->order->currency . ' ' . number_format($this->discounted_price, 2, ',', '.');
    }

    /**
     * Verifica se o item tem desconto
     */
    public function hasDiscount(): bool
    {
        return $this->discount_amount > 0;
    }

    /**
     * Verifica se o item tem impostos
     */
    public function hasTax(): bool
    {
        return $this->tax_amount > 0;
    }

    /**
     * Verifica se o item tem variações
     */
    public function hasVariant(): bool
    {
        return !is_null($this->product_variant_id);
    }

    /**
     * Verifica se o item tem opções personalizadas
     */
    public function hasOptions(): bool
    {
        return !empty($this->options);
    }

    /**
     * Obtém uma opção específica
     */
    public function getOption(string $key)
    {
        return $this->options[$key] ?? null;
    }

    /**
     * Define uma opção
     */
    public function setOption(string $key, $value): void
    {
        $options = $this->options ?? [];
        $options[$key] = $value;
        $this->options = $options;
        $this->save();
    }

    /**
     * Remove uma opção
     */
    public function removeOption(string $key): void
    {
        $options = $this->options ?? [];
        unset($options[$key]);
        $this->options = $options;
        $this->save();
    }

    /**
     * Obtém um atributo específico
     */
    public function getAttribute(string $key)
    {
        return $this->attributes[$key] ?? null;
    }

    /**
     * Define um atributo
     */
    public function setAttribute(string $key, $value): void
    {
        $attributes = $this->attributes ?? [];
        $attributes[$key] = $value;
        $this->attributes = $attributes;
        $this->save();
    }

    /**
     * Remove um atributo
     */
    public function removeAttribute(string $key): void
    {
        $attributes = $this->attributes ?? [];
        unset($attributes[$key]);
        $this->attributes = $attributes;
        $this->save();
    }

    /**
     * Obtém metadados
     */
    public function getMeta(string $key)
    {
        return $this->meta_data[$key] ?? null;
    }

    /**
     * Define metadados
     */
    public function setMeta(string $key, $value): void
    {
        $metaData = $this->meta_data ?? [];
        $metaData[$key] = $value;
        $this->meta_data = $metaData;
        $this->save();
    }

    /**
     * Remove metadados
     */
    public function removeMeta(string $key): void
    {
        $metaData = $this->meta_data ?? [];
        unset($metaData[$key]);
        $this->meta_data = $metaData;
        $this->save();
    }

    /**
     * Calcula o preço total com desconto e impostos
     */
    public function getTotalWithTaxAttribute(): float
    {
        return ($this->price - $this->discount_amount) * $this->quantity + $this->tax_amount;
    }

    /**
     * Obtém informações do produto para exibição
     */
    public function getDisplayInfoAttribute(): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'product_id' => $this->product_id,
            'product_name' => $this->product_name,
            'product_sku' => $this->product_sku,
            'variant_id' => $this->product_variant_id,
            'quantity' => $this->quantity,
            'price' => $this->price,
            'total' => $this->total,
            'formatted_price' => $this->formatted_price,
            'formatted_total' => $this->formatted_total,
            'has_discount' => $this->hasDiscount(),
            'discount_amount' => $this->discount_amount,
            'formatted_discount' => $this->formatted_discount,
            'has_tax' => $this->hasTax(),
            'tax_amount' => $this->tax_amount,
            'formatted_tax' => $this->formatted_tax,
            'discounted_price' => $this->discounted_price,
            'formatted_discounted_price' => $this->formatted_discounted_price,
            'has_variant' => $this->hasVariant(),
            'has_options' => $this->hasOptions(),
            'options' => $this->options,
            'attributes' => $this->attributes,
        ];
    }

    /**
     * Obtém estatísticas do item
     */
    public function getStatsAttribute(): array
    {
        return [
            'quantity' => $this->quantity,
            'price' => $this->price,
            'total' => $this->total,
            'discount_amount' => $this->discount_amount,
            'tax_amount' => $this->tax_amount,
            'has_discount' => $this->hasDiscount(),
            'has_tax' => $this->hasTax(),
            'has_variant' => $this->hasVariant(),
            'has_options' => $this->hasOptions(),
            'total_with_tax' => $this->total_with_tax,
        ];
    }

    /**
     * Calcula o percentual de desconto
     */
    public function getDiscountPercentageAttribute(): float
    {
        if ($this->price <= 0) {
            return 0;
        }

        return round(($this->discount_amount / $this->price) * 100, 2);
    }

    /**
     * Calcula o percentual de impostos
     */
    public function getTaxPercentageAttribute(): float
    {
        $basePrice = $this->price - $this->discount_amount;

        if ($basePrice <= 0) {
            return 0;
        }

        return round(($this->tax_amount / $basePrice) * 100, 2);
    }

    /**
     * Verifica se o item pode ser reembolsado
     */
    public function canBeRefunded(): bool
    {
        return $this->order->canBeRefunded() && $this->total > 0;
    }

    /**
     * Verifica se o item pode ser devolvido
     */
    public function canBeReturned(): bool
    {
        return $this->order->isDelivered() && $this->total > 0;
    }

    /**
     * Obtém informações de reembolso
     */
    public function getRefundInfoAttribute(): array
    {
        return [
            'can_be_refunded' => $this->canBeRefunded(),
            'can_be_returned' => $this->canBeReturned(),
            'refundable_amount' => $this->total,
            'formatted_refundable_amount' => $this->formatted_total,
        ];
    }
}