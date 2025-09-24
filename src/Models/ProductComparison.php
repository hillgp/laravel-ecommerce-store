<?php

namespace SupernovaCorp\LaravelEcommerceStore\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ProductComparison extends Model
{
    protected $fillable = [
        'session_id',
        'customer_id',
        'name',
        'max_products',
        'expires_at',
        'is_active',
        'meta'
    ];

    protected $casts = [
        'max_products' => 'integer',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
        'meta' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    protected $attributes = [
        'max_products' => 4,
        'is_active' => true
    ];

    /**
     * Boot do modelo
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($comparison) {
            if (!$comparison->session_id) {
                $comparison->session_id = static::generateSessionId();
            }

            if (!$comparison->expires_at) {
                $comparison->expires_at = Carbon::now()->addDays(7);
            }
        });

        static::updating(function ($comparison) {
            if ($comparison->expires_at && $comparison->expires_at->isPast()) {
                $comparison->is_active = false;
            }
        });
    }

    /**
     * Produtos da comparação
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_comparison_items')
                    ->withPivot(['sort_order', 'notes', 'added_at'])
                    ->withTimestamps()
                    ->orderBy('product_comparison_items.sort_order')
                    ->orderBy('product_comparison_items.created_at', 'desc');
    }

    /**
     * Cliente proprietário (opcional)
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Verifica se a comparação pertence ao usuário autenticado
     */
    public function belongsToAuthUser(): bool
    {
        if (!auth('customer')->check()) {
            return false;
        }

        return $this->customer_id === auth('customer')->id();
    }

    /**
     * Verifica se a comparação está ativa
     */
    public function isActive(): bool
    {
        return $this->is_active && (!$this->expires_at || $this->expires_at->isFuture());
    }

    /**
     * Verifica se a comparação expirou
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Verifica se pode adicionar mais produtos
     */
    public function canAddMoreProducts(): bool
    {
        return $this->products()->count() < $this->max_products;
    }

    /**
     * Obtém o número de produtos na comparação
     */
    public function getProductsCountAttribute(): int
    {
        return $this->products()->count();
    }

    /**
     * Obtém produtos em promoção
     */
    public function getOnSaleProductsAttribute(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->products()
                    ->whereNotNull('compare_price')
                    ->whereRaw('price < compare_price')
                    ->get();
    }

    /**
     * Obtém produtos em estoque
     */
    public function getInStockProductsAttribute(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->products()
                    ->where('stock_quantity', '>', 0)
                    ->get();
    }

    /**
     * Obtém produtos fora de estoque
     */
    public function getOutOfStockProductsAttribute(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->products()
                    ->where('stock_quantity', '=', 0)
                    ->get();
    }

    /**
     * Adiciona produto à comparação
     */
    public function addProduct(int $productId, string $notes = null): bool
    {
        if (!$this->canAddMoreProducts()) {
            return false;
        }

        // Verifica se produto já está na comparação
        if ($this->products()->where('product_id', $productId)->exists()) {
            return true;
        }

        $this->products()->attach($productId, [
            'notes' => $notes,
            'added_at' => Carbon::now()
        ]);

        return true;
    }

    /**
     * Remove produto da comparação
     */
    public function removeProduct(int $productId): bool
    {
        return $this->products()->detach($productId) > 0;
    }

    /**
     * Verifica se produto está na comparação
     */
    public function hasProduct(int $productId): bool
    {
        return $this->products()->where('product_id', $productId)->exists();
    }

    /**
     * Limpa comparação
     */
    public function clear(): bool
    {
        return $this->products()->detach() > 0;
    }

    /**
     * Obtém produto específico
     */
    public function getProduct(int $productId): ?Product
    {
        return $this->products()->where('product_id', $productId)->first();
    }

    /**
     * Compartilha comparação
     */
    public function share(): string
    {
        $this->update(['is_active' => true]);

        return route('store.comparison.shared', ['token' => $this->generateShareToken()]);
    }

    /**
     * Gera token para compartilhamento
     */
    public function generateShareToken(): string
    {
        return encrypt([
            'comparison_id' => $this->id,
            'expires_at' => Carbon::now()->addDays(30)->timestamp
        ]);
    }

    /**
     * Obtém comparação por token de compartilhamento
     */
    public static function findByShareToken(string $token): ?ProductComparison
    {
        try {
            $data = decrypt($token);

            if ($data['expires_at'] < Carbon::now()->timestamp) {
                return null;
            }

            return static::find($data['comparison_id']);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Obtém estatísticas da comparação
     */
    public function getStatsAttribute(): array
    {
        $products = $this->products;

        return [
            'total_products' => $products->count(),
            'max_products' => $this->max_products,
            'on_sale_products' => $products->filter(fn($product) => $product->hasDiscount())->count(),
            'in_stock_products' => $products->filter(fn($product) => $product->inStock())->count(),
            'out_of_stock_products' => $products->filter(fn($product) => $product->outOfStock())->count(),
            'average_price' => $products->avg('price'),
            'min_price' => $products->min('price'),
            'max_price' => $products->max('price'),
            'categories_count' => $products->pluck('category_id')->unique()->count(),
            'brands_count' => $products->pluck('brand_id')->unique()->count()
        ];
    }

    /**
     * Obtém dados de comparação entre produtos
     */
    public function getComparisonDataAttribute(): array
    {
        $products = $this->products;

        if ($products->isEmpty()) {
            return [];
        }

        // Campos para comparação
        $comparisonFields = [
            'price' => 'Preço',
            'compare_price' => 'Preço Original',
            'stock_quantity' => 'Estoque',
            'weight' => 'Peso',
            'dimensions' => 'Dimensões',
            'brand' => 'Marca',
            'category' => 'Categoria',
            'sku' => 'SKU',
            'rating' => 'Avaliação',
            'review_count' => 'Nº de Avaliações'
        ];

        $comparisonData = [];

        foreach ($comparisonFields as $field => $label) {
            $values = $products->pluck($field)->unique()->values();

            if ($values->count() > 1) {
                $comparisonData[$field] = [
                    'label' => $label,
                    'values' => $values,
                    'has_difference' => true
                ];
            } else {
                $comparisonData[$field] = [
                    'label' => $label,
                    'values' => $values,
                    'has_difference' => false
                ];
            }
        }

        return $comparisonData;
    }

    /**
     * Scopes
     */

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                    ->where(function ($q) {
                        $q->whereNull('expires_at')
                          ->orWhere('expires_at', '>', now());
                    });
    }

    public function scopeBySession($query, string $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }

    public function scopeByCustomer($query, int $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopeWithProducts($query)
    {
        return $query->with('products');
    }

    public function scopeWithStats($query)
    {
        return $query->withCount('products');
    }

    /**
     * Métodos estáticos
     */

    /**
     * Gera ID de sessão único
     */
    public static function generateSessionId(): string
    {
        do {
            $sessionId = Str::random(40);
        } while (static::where('session_id', $sessionId)->exists());

        return $sessionId;
    }

    /**
     * Obtém comparação atual da sessão
     */
    public static function getCurrent(?string $sessionId = null): ProductComparison
    {
        $sessionId = $sessionId ?: static::getSessionIdFromCookie();

        $comparison = static::where('session_id', $sessionId)
                           ->where('is_active', true)
                           ->first();

        if (!$comparison) {
            $comparison = static::create([
                'session_id' => $sessionId,
                'name' => 'Comparação de Produtos'
            ]);
        }

        return $comparison;
    }

    /**
     * Obtém ID da sessão do cookie
     */
    public static function getSessionIdFromCookie(): string
    {
        $sessionId = Cookie::get('product_comparison_session');

        if (!$sessionId) {
            $sessionId = static::generateSessionId();
            Cookie::queue('product_comparison_session', $sessionId, 60 * 24 * 7); // 7 dias
        }

        return $sessionId;
    }

    /**
     * Obtém comparações do cliente
     */
    public static function getForCustomer(int $customerId): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('customer_id', $customerId)
                    ->orderBy('created_at', 'desc')
                    ->get();
    }

    /**
     * Obtém produtos mais comparados
     */
    public static function getMostComparedProducts(int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return Product::select('products.*')
                     ->join('product_comparison_items', 'products.id', '=', 'product_comparison_items.product_id')
                     ->join('product_comparisons', 'product_comparison_items.product_comparison_id', '=', 'product_comparisons.id')
                     ->where('product_comparisons.is_active', true)
                     ->groupBy('products.id')
                     ->orderByRaw('COUNT(*) DESC')
                     ->limit($limit)
                     ->get();
    }

    /**
     * Obtém estatísticas gerais de comparações
     */
    public static function getGlobalStats(): array
    {
        $totalComparisons = static::count();
        $activeComparisons = static::active()->count();
        $totalProducts = \DB::table('product_comparison_items')->count();
        $averageProductsPerComparison = $totalComparisons > 0 ? $totalProducts / $totalComparisons : 0;

        return [
            'total_comparisons' => $totalComparisons,
            'active_comparisons' => $activeComparisons,
            'total_products' => $totalProducts,
            'average_products_per_comparison' => round($averageProductsPerComparison, 2),
            'most_compared_product' => static::getMostComparedProducts(1)->first()
        ];
    }

    /**
     * Limpa comparações expiradas
     */
    public static function cleanupExpired(): int
    {
        return static::where('expires_at', '<', now())
                    ->orWhere('expires_at', '<', now()->subDays(30))
                    ->update(['is_active' => false]);
    }

    /**
     * Exporta comparação para array
     */
    public function toArray(): array
    {
        $array = parent::toArray();

        $array['products_count'] = $this->products_count;
        $array['stats'] = $this->stats;
        $array['comparison_data'] = $this->comparison_data;
        $array['can_add_more'] = $this->canAddMoreProducts();

        return $array;
    }

    /**
     * Exporta comparação para JSON
     */
    public function toJson($options = 0): string
    {
        return json_encode($this->toArray(), $options);
    }
}