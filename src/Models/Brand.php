<?php

namespace LaravelEcommerce\Store\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Brand extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Nome da tabela
     */
    protected $table = 'brands';

    /**
     * Campos preenchíveis
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'logo',
        'website',
        'email',
        'phone',
        'is_active',
        'is_featured',
        'sort_order',
        'meta_data',
    ];

    /**
     * Campos que devem ser convertidos para tipos específicos
     */
    protected $casts = [
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'sort_order' => 'integer',
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
     * Boot do modelo
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($brand) {
            if (empty($brand->slug)) {
                $brand->slug = Str::slug($brand->name);
            }
        });

        static::updating(function ($brand) {
            if ($brand->isDirty('name') && empty($brand->slug)) {
                $brand->slug = Str::slug($brand->name);
            }
        });
    }

    /**
     * Produtos desta marca
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'brand_id');
    }

    /**
     * Produtos ativos desta marca
     */
    public function activeProducts(): HasMany
    {
        return $this->products()->where('is_active', true);
    }

    /**
     * Produtos em destaque desta marca
     */
    public function featuredProducts(): HasMany
    {
        return $this->products()->where('is_featured', true)->where('is_active', true);
    }

    /**
     * Produtos em promoção desta marca
     */
    public function saleProducts(): HasMany
    {
        return $this->products()->where('is_on_sale', true)->where('is_active', true);
    }

    /**
     * Scope para marcas ativas
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope para marcas em destaque
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true)->where('is_active', true);
    }

    /**
     * Scope para ordenação padrão
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Verifica se a marca tem produtos
     */
    public function hasProducts(): bool
    {
        return $this->products()->exists();
    }

    /**
     * Obtém a URL do logo da marca
     */
    public function getLogoUrlAttribute(): string
    {
        if ($this->logo) {
            return asset('storage/brands/' . $this->logo);
        }

        return asset('images/no-brand.png');
    }

    /**
     * Obtém o número total de produtos da marca
     */
    public function getProductsCountAttribute(): int
    {
        return $this->products()->count();
    }

    /**
     * Obtém o número de produtos ativos da marca
     */
    public function getActiveProductsCountAttribute(): int
    {
        return $this->activeProducts()->count();
    }

    /**
     * Obtém estatísticas da marca
     */
    public function getStatsAttribute(): array
    {
        return [
            'total_products' => $this->products_count,
            'active_products' => $this->active_products_count,
            'featured_products' => $this->featuredProducts()->count(),
            'sale_products' => $this->saleProducts()->count(),
            'has_products' => $this->hasProducts(),
        ];
    }

    /**
     * Obtém o preço médio dos produtos da marca
     */
    public function getAveragePriceAttribute(): float
    {
        return $this->products()->avg('price') ?? 0;
    }

    /**
     * Obtém o preço mínimo dos produtos da marca
     */
    public function getMinPriceAttribute(): float
    {
        return $this->products()->min('price') ?? 0;
    }

    /**
     * Obtém o preço máximo dos produtos da marca
     */
    public function getMaxPriceAttribute(): float
    {
        return $this->products()->max('price') ?? 0;
    }

    /**
     * Obtém informações de contato formatadas
     */
    public function getContactInfoAttribute(): array
    {
        return [
            'website' => $this->website,
            'email' => $this->email,
            'phone' => $this->phone,
        ];
    }

    /**
     * Verifica se a marca tem informações de contato
     */
    public function hasContactInfo(): bool
    {
        return !empty($this->website) || !empty($this->email) || !empty($this->phone);
    }

    /**
     * Obtém produtos mais vendidos da marca
     */
    public function getBestSellingProducts($limit = 10)
    {
        return $this->products()
            ->leftJoin('order_items', 'products.id', '=', 'order_items.product_id')
            ->selectRaw('products.*, SUM(order_items.quantity) as total_sold')
            ->groupBy('products.id')
            ->orderByDesc('total_sold')
            ->limit($limit)
            ->get();
    }

    /**
     * Obtém produtos mais visualizados da marca
     */
    public function getMostViewedProducts($limit = 10)
    {
        return $this->products()
            ->where('is_active', true)
            ->orderByDesc('view_count')
            ->limit($limit)
            ->get();
    }

    /**
     * Obtém produtos mais bem avaliados da marca
     */
    public function getHighestRatedProducts($limit = 10)
    {
        return $this->products()
            ->where('is_active', true)
            ->where('rating', '>', 0)
            ->orderByDesc('rating')
            ->limit($limit)
            ->get();
    }
}