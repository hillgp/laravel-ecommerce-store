<?php

namespace LaravelEcommerce\Store\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CartItem extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Nome da tabela
     */
    protected $table = 'cart_items';

    /**
     * Campos preenchíveis
     */
    protected $fillable = [
        'cart_id',
        'product_id',
        'product_variant_id',
        'quantity',
        'price',
        'total',
        'options',
        'meta_data',
    ];

    /**
     * Campos que devem ser convertidos para tipos específicos
     */
    protected $casts = [
        'cart_id' => 'integer',
        'product_id' => 'integer',
        'product_variant_id' => 'integer',
        'quantity' => 'integer',
        'price' => 'decimal:2',
        'total' => 'decimal:2',
        'options' => 'array',
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
     * Carrinho ao qual o item pertence
     */
    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class, 'cart_id');
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
     * Scope para itens ativos
     */
    public function scopeActive($query)
    {
        return $query->whereHas('product', function ($q) {
            $q->where('is_active', true);
        });
    }

    /**
     * Verifica se o item pode ser comprado
     */
    public function canBePurchased(): bool
    {
        if (!$this->product->is_active) {
            return false;
        }

        if ($this->variant && !$this->variant->is_active) {
            return false;
        }

        return $this->product->isInStock() && ($this->variant ? $this->variant->isInStock() : true);
    }

    /**
     * Obtém o preço unitário atual do produto/variação
     */
    public function getCurrentPrice(): float
    {
        if ($this->variant) {
            return $this->variant->getFinalPrice();
        }

        return $this->product->getFinalPrice();
    }

    /**
     * Obtém o nome do produto
     */
    public function getProductNameAttribute(): string
    {
        $name = $this->product->name;

        if ($this->variant && $this->variant->hasOptions()) {
            $name .= ' - ' . $this->variant->formatted_options;
        }

        return $name;
    }

    /**
     * Obtém a imagem do produto
     */
    public function getProductImageAttribute(): string
    {
        if ($this->variant && $this->variant->hasCustomImage()) {
            return $this->variant->image_url;
        }

        if ($this->product->primaryImage) {
            return $this->product->primaryImage->url;
        }

        return asset('images/no-image.png');
    }

    /**
     * Obtém o SKU do produto
     */
    public function getProductSkuAttribute(): string
    {
        if ($this->variant) {
            return $this->variant->sku;
        }

        return $this->product->sku;
    }

    /**
     * Obtém o peso do item (considerando quantidade)
     */
    public function getTotalWeightAttribute(): float
    {
        $weight = $this->variant ? $this->variant->weight : $this->product->weight;
        return $weight ? $weight * $this->quantity : 0;
    }

    /**
     * Obtém o preço total formatado
     */
    public function getFormattedTotalAttribute(): string
    {
        return 'R$ ' . number_format($this->total, 2, ',', '.');
    }

    /**
     * Obtém o preço unitário formatado
     */
    public function getFormattedPriceAttribute(): string
    {
        return 'R$ ' . number_format($this->price, 2, ',', '.');
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
     * Atualiza a quantidade do item
     */
    public function updateQuantity(int $quantity): void
    {
        $this->update([
            'quantity' => $quantity,
            'total' => $quantity * $this->price,
        ]);
    }

    /**
     * Incrementa a quantidade
     */
    public function incrementQuantity(int $amount = 1): void
    {
        $this->updateQuantity($this->quantity + $amount);
    }

    /**
     * Decrementa a quantidade
     */
    public function decrementQuantity(int $amount = 1): void
    {
        $newQuantity = max(0, $this->quantity - $amount);
        $this->updateQuantity($newQuantity);
    }

    /**
     * Verifica se a quantidade pode ser alterada
     */
    public function canChangeQuantity(int $newQuantity): bool
    {
        // Verifica se o produto/variação está disponível
        if (!$this->canBePurchased()) {
            return false;
        }

        // Verifica se há estoque suficiente
        $availableQuantity = $this->variant ?
            $this->variant->getAvailableQuantity() :
            $this->product->getAvailableQuantity();

        return $newQuantity <= $availableQuantity;
    }

    /**
     * Obtém informações do produto para exibição
     */
    public function getDisplayInfoAttribute(): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'product_name' => $this->product_name,
            'product_sku' => $this->product_sku,
            'product_image' => $this->product_image,
            'variant_id' => $this->product_variant_id,
            'quantity' => $this->quantity,
            'price' => $this->price,
            'total' => $this->total,
            'formatted_price' => $this->formatted_price,
            'formatted_total' => $this->formatted_total,
            'has_variant' => $this->hasVariant(),
            'has_options' => $this->hasOptions(),
            'options' => $this->options,
            'can_be_purchased' => $this->canBePurchased(),
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
            'has_variant' => $this->hasVariant(),
            'has_options' => $this->hasOptions(),
            'can_be_purchased' => $this->canBePurchased(),
            'current_price' => $this->getCurrentPrice(),
            'total_weight' => $this->total_weight,
        ];
    }
}