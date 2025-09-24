<?php

namespace LaravelEcommerce\Store\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use LaravelEcommerce\Store\Traits\HasImages;
use LaravelEcommerce\Store\Traits\HasReviews;
use LaravelEcommerce\Store\Traits\HasInventory;

class Product extends Model
{
    use HasFactory, SoftDeletes, HasImages, HasReviews, HasInventory;

    /**
     * Nome da tabela
     */
    protected $table = 'products';

    /**
     * Campos preenchíveis
     */
    protected $fillable = [
        'name',
        'slug',
        'sku',
        'description',
        'short_description',
        'price',
        'compare_price',
        'cost_price',
        'stock_quantity',
        'min_stock_quantity',
        'track_stock',
        'allow_backorders',
        'is_virtual',
        'is_downloadable',
        'is_active',
        'is_featured',
        'is_on_sale',
        'requires_shipping',
        'weight',
        'length',
        'width',
        'height',
        'unit',
        'category_id',
        'brand_id',
        'sort_order',
        'view_count',
        'rating',
        'review_count',
        'tags',
        'attributes',
        'meta_data',
        'published_at',
    ];

    /**
     * Campos que devem ser convertidos para tipos específicos
     */
    protected $casts = [
        'price' => 'decimal:2',
        'compare_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'stock_quantity' => 'integer',
        'min_stock_quantity' => 'integer',
        'track_stock' => 'boolean',
        'allow_backorders' => 'boolean',
        'is_virtual' => 'boolean',
        'is_downloadable' => 'boolean',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'is_on_sale' => 'boolean',
        'requires_shipping' => 'boolean',
        'weight' => 'decimal:3',
        'length' => 'decimal:2',
        'width' => 'decimal:2',
        'height' => 'decimal:2',
        'sort_order' => 'integer',
        'view_count' => 'integer',
        'rating' => 'decimal:2',
        'review_count' => 'integer',
        'tags' => 'array',
        'attributes' => 'array',
        'meta_data' => 'array',
        'published_at' => 'datetime',
    ];

    /**
     * Campos que devem ser ocultos na serialização
     */
    protected $hidden = [
        'cost_price',
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

        static::creating(function ($product) {
            if (empty($product->slug)) {
                $product->slug = Str::slug($product->name);
            }
            if (empty($product->sku)) {
                $product->sku = 'PROD-' . strtoupper(Str::random(8));
            }
        });

        static::updating(function ($product) {
            if ($product->isDirty('name') && empty($product->slug)) {
                $product->slug = Str::slug($product->name);
            }
        });
    }

    /**
     * Categoria principal do produto
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    /**
     * Marca do produto
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }

    /**
     * Todas as categorias do produto
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'product_categories')
            ->withPivot('is_primary', 'sort_order')
            ->orderByPivot('sort_order');
    }

    /**
     * Variações do produto
     */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class, 'product_id');
    }

    /**
     * Variação padrão do produto
     */
    public function defaultVariant(): HasMany
    {
        return $this->variants()->where('is_default', true);
    }

    /**
     * Scope para produtos ativos
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope para produtos em destaque
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true)->where('is_active', true);
    }

    /**
     * Scope para produtos em promoção
     */
    public function scopeOnSale($query)
    {
        return $query->where('is_on_sale', true)->where('is_active', true);
    }

    /**
     * Scope para produtos em estoque
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
     * Scope para produtos virtuais
     */
    public function scopeVirtual($query)
    {
        return $query->where('is_virtual', true);
    }

    /**
     * Scope para produtos baixáveis
     */
    public function scopeDownloadable($query)
    {
        return $query->where('is_downloadable', true);
    }

    /**
     * Scope para produtos físicos
     */
    public function scopePhysical($query)
    {
        return $query->where('is_virtual', false);
    }

    /**
     * Scope para ordenação padrão
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Scope para busca por nome ou descrição
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%")
              ->orWhere('short_description', 'like', "%{$search}%")
              ->orWhere('sku', 'like', "%{$search}%");
        });
    }

    /**
     * Scope para filtrar por preço
     */
    public function scopePriceBetween($query, float $min, float $max)
    {
        return $query->whereBetween('price', [$min, $max]);
    }

    /**
     * Scope para filtrar por categoria
     */
    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * Scope para filtrar por marca
     */
    public function scopeByBrand($query, $brandId)
    {
        return $query->where('brand_id', $brandId);
    }

    /**
     * Verifica se o produto está em estoque
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
     * Verifica se o produto pode ser comprado
     */
    public function canBePurchased(): bool
    {
        return $this->is_active && $this->isInStock();
    }

    /**
     * Obtém o preço final do produto (considerando promoções)
     */
    public function getFinalPrice(): float
    {
        return $this->price;
    }

    /**
     * Obtém o desconto em percentual
     */
    public function getDiscountPercentage(): float
    {
        if (!$this->compare_price || $this->compare_price <= $this->price) {
            return 0;
        }

        return round((($this->compare_price - $this->price) / $this->compare_price) * 100, 2);
    }

    /**
     * Verifica se o produto está em promoção
     */
    public function isOnSale(): bool
    {
        return $this->is_on_sale && $this->compare_price > $this->price;
    }

    /**
     * Obtém o preço de venda (menor preço entre variações)
     */
    public function getSalePrice(): float
    {
        if ($this->variants()->exists()) {
            return $this->variants()->min('price') ?? $this->price;
        }

        return $this->price;
    }

    /**
     * Obtém o preço mais alto entre variações
     */
    public function getHighestPrice(): float
    {
        if ($this->variants()->exists()) {
            return $this->variants()->max('price') ?? $this->price;
        }

        return $this->price;
    }

    /**
     * Obtém as dimensões formatadas
     */
    public function getFormattedDimensionsAttribute(): string
    {
        if (!$this->length || !$this->width || !$this->height) {
            return '';
        }

        return "{$this->length} x {$this->width} x {$this->height} cm";
    }

    /**
     * Obtém o peso formatado
     */
    public function getFormattedWeightAttribute(): string
    {
        if (!$this->weight) {
            return '';
        }

        return number_format($this->weight, 3, ',', '.') . ' kg';
    }

    /**
     * Obtém as tags como string
     */
    public function getTagsStringAttribute(): string
    {
        if (empty($this->tags)) {
            return '';
        }

        return implode(', ', $this->tags);
    }

    /**
     * Incrementa o contador de visualizações
     */
    public function incrementViewCount(): void
    {
        $this->increment('view_count');
    }

    /**
     * Atualiza a avaliação do produto
     */
    public function updateRating(): void
    {
        $this->rating = $this->approvedReviews()->avg('rating') ?? 0;
        $this->review_count = $this->approvedReviews()->count();
        $this->save();
    }

    /**
     * Obtém produtos relacionados
     */
    public function getRelatedProducts($limit = 8)
    {
        return self::where('category_id', $this->category_id)
            ->where('id', '!=', $this->id)
            ->where('is_active', true)
            ->inRandomOrder()
            ->limit($limit)
            ->get();
    }

    /**
     * Obtém produtos frequentemente comprados juntos
     */
    public function getFrequentlyBoughtTogether($limit = 4)
    {
        return self::whereHas('orders', function ($query) {
            $query->whereHas('order', function ($q) {
                $q->whereHas('items', function ($qi) {
                    $qi->where('product_id', $this->id);
                });
            });
        })
        ->where('id', '!=', $this->id)
        ->where('is_active', true)
        ->inRandomOrder()
        ->limit($limit)
        ->get();
    }

    /**
     * Obtém estatísticas do produto
     */
    public function getStatsAttribute(): array
    {
        return [
            'views' => $this->view_count,
            'rating' => $this->rating,
            'reviews' => $this->review_count,
            'in_stock' => $this->isInStock(),
            'available_quantity' => $this->getAvailableQuantity(),
            'on_sale' => $this->isOnSale(),
            'discount_percentage' => $this->getDiscountPercentage(),
            'has_variants' => $this->variants()->exists(),
            'variants_count' => $this->variants()->count(),
        ];
    }
}