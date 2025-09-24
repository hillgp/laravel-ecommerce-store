<?php

namespace LaravelEcommerce\Store\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Category extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Nome da tabela
     */
    protected $table = 'categories';

    /**
     * Campos preenchíveis
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'image',
        'icon',
        'parent_id',
        'sort_order',
        'is_active',
        'is_featured',
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
        'published_at' => 'datetime',
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

        static::creating(function ($category) {
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
        });

        static::updating(function ($category) {
            if ($category->isDirty('name') && empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
        });
    }

    /**
     * Categoria pai
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /**
     * Subcategorias
     */
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    /**
     * Todos os produtos desta categoria
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'category_id');
    }

    /**
     * Produtos ativos desta categoria
     */
    public function activeProducts(): HasMany
    {
        return $this->products()->where('is_active', true);
    }

    /**
     * Produtos em destaque desta categoria
     */
    public function featuredProducts(): HasMany
    {
        return $this->products()->where('is_featured', true)->where('is_active', true);
    }

    /**
     * Produtos em promoção desta categoria
     */
    public function saleProducts(): HasMany
    {
        return $this->products()->where('is_on_sale', true)->where('is_active', true);
    }

    /**
     * Scope para categorias ativas
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope para categorias em destaque
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true)->where('is_active', true);
    }

    /**
     * Scope para categorias principais (sem pai)
     */
    public function scopeParents($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope para ordenação padrão
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Verifica se a categoria tem subcategorias
     */
    public function hasChildren(): bool
    {
        return $this->children()->exists();
    }

    /**
     * Verifica se a categoria tem produtos
     */
    public function hasProducts(): bool
    {
        return $this->products()->exists();
    }

    /**
     * Obtém o caminho completo da categoria (breadcrumb)
     */
    public function getFullPathAttribute(): string
    {
        $path = [$this->name];
        $parent = $this->parent;

        while ($parent) {
            array_unshift($path, $parent->name);
            $parent = $parent->parent;
        }

        return implode(' > ', $path);
    }

    /**
     * Obtém a URL da imagem da categoria
     */
    public function getImageUrlAttribute(): string
    {
        if ($this->image) {
            return asset('storage/categories/' . $this->image);
        }

        return asset('images/no-category.png');
    }

    /**
     * Obtém o ícone da categoria
     */
    public function getIconHtmlAttribute(): string
    {
        if ($this->icon) {
            return '<i class="' . $this->icon . '"></i>';
        }

        return '<i class="fas fa-folder"></i>';
    }

    /**
     * Obtém o número total de produtos (incluindo subcategorias)
     */
    public function getTotalProductsCountAttribute(): int
    {
        $count = $this->products()->count();

        foreach ($this->children as $child) {
            $count += $child->total_products_count;
        }

        return $count;
    }

    /**
     * Obtém estatísticas da categoria
     */
    public function getStatsAttribute(): array
    {
        return [
            'total_products' => $this->total_products_count,
            'active_products' => $this->activeProducts()->count(),
            'featured_products' => $this->featuredProducts()->count(),
            'sale_products' => $this->saleProducts()->count(),
            'has_children' => $this->hasChildren(),
            'children_count' => $this->children()->count(),
        ];
    }
}