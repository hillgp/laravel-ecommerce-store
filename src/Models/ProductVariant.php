<?php

namespace LaravelEcommerce\Store\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ProductVariant extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Nome da tabela
     */
    protected $table = 'product_variants';

    /**
     * Campos preenchíveis
     */
    protected $fillable = [
        'product_id',
        'name',
        'sku',
        'price',
        'compare_price',
        'cost_price',
        'stock_quantity',
        'min_stock_quantity',
        'track_stock',
        'allow_backorders',
        'weight',
        'length',
        'width',
        'height',
        'barcode',
        'image',
        'options',
        'attributes',
        'is_active',
        'is_default',
        'sort_order',
    ];

    /**
     * Campos que devem ser convertidos para tipos específicos
     */
    protected $casts = [
        'product_id' => 'integer',
        'price' => 'decimal:2',
        'compare_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'stock_quantity' => 'integer',
        'min_stock_quantity' => 'integer',
        'track_stock' => 'boolean',
        'allow_backorders' => 'boolean',
        'weight' => 'decimal:3',
        'length' => 'decimal:2',
        'width' => 'decimal:2',
        'height' => 'decimal:2',
        'options' => 'array',
        'attributes' => 'array',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'sort_order' => 'integer',
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

        static::creating(function ($variant) {
            if (empty($variant->sku)) {
                $variant->sku = 'VAR-' . strtoupper(Str::random(8));
            }
        });
    }

    /**
     * Produto pai da variação
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Scope para variações ativas
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope para variações padrão
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Scope para variações em estoque
     */
    public function scopeInStock($query)
    {
        return $query->where(function ($q) {
            $q->where('track_stock', false)
              ->orWhere('stock_quantity', '>', 0)
              ->orWhere('allow_backorders', true);
        });
    }

    /**
     * Scope para ordenação padrão
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Verifica se a variação está em estoque
     */
    public function isInStock(): bool
    {
        if (!$this->track_stock) {
            return true;
        }

        return $this->stock_quantity > 0 || $this->allow_backorders;
    }

    /**
     * Obtém a quantidade disponível
     */
    public function getAvailableQuantity(): int
    {
        if (!$this->track_stock) {
            return 999;
        }

        return max(0, $this->stock_quantity);
    }

    /**
     * Verifica se a variação pode ser comprada
     */
    public function canBePurchased(): bool
    {
        return $this->is_active && $this->isInStock();
    }

    /**
     * Obtém o preço final da variação
     */
    public function getFinalPrice(): float
    {
        return $this->price ?? $this->product->price;
    }

    /**
     * Obtém o preço de comparação da variação
     */
    public function getComparePrice(): float
    {
        return $this->compare_price ?? $this->product->compare_price;
    }

    /**
     * Obtém o preço de custo da variação
     */
    public function getCostPrice(): float
    {
        return $this->cost_price ?? $this->product->cost_price;
    }

    /**
     * Obtém o desconto em percentual
     */
    public function getDiscountPercentage(): float
    {
        $comparePrice = $this->getComparePrice();

        if (!$comparePrice || $comparePrice <= $this->getFinalPrice()) {
            return 0;
        }

        return round((($comparePrice - $this->getFinalPrice()) / $comparePrice) * 100, 2);
    }

    /**
     * Verifica se a variação está em promoção
     */
    public function isOnSale(): bool
    {
        $comparePrice = $this->getComparePrice();
        return $comparePrice > $this->getFinalPrice();
    }

    /**
     * Obtém as dimensões formatadas
     */
    public function getFormattedDimensionsAttribute(): string
    {
        $dimensions = [];

        if ($this->length || $this->product->length) {
            $dimensions[] = ($this->length ?? $this->product->length) . 'cm';
        }

        if ($this->width || $this->product->width) {
            $dimensions[] = ($this->width ?? $this->product->width) . 'cm';
        }

        if ($this->height || $this->product->height) {
            $dimensions[] = ($this->height ?? $this->product->height) . 'cm';
        }

        return implode(' x ', $dimensions);
    }

    /**
     * Obtém o peso formatado
     */
    public function getFormattedWeightAttribute(): string
    {
        $weight = $this->weight ?? $this->product->weight;

        if (!$weight) {
            return '';
        }

        return number_format($weight, 3, ',', '.') . ' kg';
    }

    /**
     * Obtém a URL da imagem da variação
     */
    public function getImageUrlAttribute(): string
    {
        if ($this->image) {
            return asset('storage/products/' . $this->image);
        }

        if ($this->product->primaryImage) {
            return $this->product->primaryImage->url;
        }

        return asset('images/no-image.png');
    }

    /**
     * Obtém as opções formatadas como string
     */
    public function getFormattedOptionsAttribute(): string
    {
        if (empty($this->options)) {
            return '';
        }

        $options = [];
        foreach ($this->options as $key => $value) {
            $options[] = ucfirst($key) . ': ' . $value;
        }

        return implode(', ', $options);
    }

    /**
     * Obtém o nome completo da variação (produto + variação)
     */
    public function getFullNameAttribute(): string
    {
        $name = $this->product->name;

        if (!empty($this->options)) {
            $name .= ' - ' . $this->formatted_options;
        }

        return $name;
    }

    /**
     * Obtém estatísticas da variação
     */
    public function getStatsAttribute(): array
    {
        return [
            'in_stock' => $this->isInStock(),
            'available_quantity' => $this->getAvailableQuantity(),
            'on_sale' => $this->isOnSale(),
            'discount_percentage' => $this->getDiscountPercentage(),
            'has_custom_price' => !is_null($this->price),
            'has_custom_image' => !is_null($this->image),
            'has_options' => !empty($this->options),
        ];
    }

    /**
     * Verifica se a variação tem preço personalizado
     */
    public function hasCustomPrice(): bool
    {
        return !is_null($this->price);
    }

    /**
     * Verifica se a variação tem imagem personalizada
     */
    public function hasCustomImage(): bool
    {
        return !is_null($this->image);
    }

    /**
     * Verifica se a variação tem opções
     */
    public function hasOptions(): bool
    {
        return !empty($this->options);
    }

    /**
     * Obtém o valor de uma opção específica
     */
    public function getOption(string $key)
    {
        return $this->options[$key] ?? null;
    }

    /**
     * Define o valor de uma opção
     */
    public function setOption(string $key, $value): void
    {
        $options = $this->options ?? [];
        $options[$key] = $value;
        $this->options = $options;
    }

    /**
     * Remove uma opção
     */
    public function removeOption(string $key): void
    {
        $options = $this->options ?? [];
        unset($options[$key]);
        $this->options = $options;
    }

    /**
     * Obtém o atributo de uma opção específica
     */
    public function getAttribute(string $key)
    {
        return $this->attributes[$key] ?? null;
    }

    /**
     * Define o valor de um atributo
     */
    public function setAttribute(string $key, $value): void
    {
        $attributes = $this->attributes ?? [];
        $attributes[$key] = $value;
        $this->attributes = $attributes;
    }

    /**
     * Remove um atributo
     */
    public function removeAttribute(string $key): void
    {
        $attributes = $this->attributes ?? [];
        unset($attributes[$key]);
        $this->attributes = $attributes;
    }
}