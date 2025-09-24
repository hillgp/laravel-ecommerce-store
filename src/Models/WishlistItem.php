<?php

namespace SupernovaCorp\LaravelEcommerceStore\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class WishlistItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'wishlist_id',
        'product_id',
        'quantity',
        'notes',
        'sort_order',
        'added_at',
        'meta'
    ];

    protected $casts = [
        'quantity' => 'integer',
        'sort_order' => 'integer',
        'added_at' => 'datetime',
        'meta' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    protected $attributes = [
        'quantity' => 1,
        'sort_order' => 0
    ];

    /**
     * Boot do modelo
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($item) {
            if (!$item->added_at) {
                $item->added_at = Carbon::now();
            }
        });
    }

    /**
     * Wishlist proprietária
     */
    public function wishlist(): BelongsTo
    {
        return $this->belongsTo(Wishlist::class);
    }

    /**
     * Produto da wishlist
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Verifica se o produto está em estoque
     */
    public function isInStock(): bool
    {
        return $this->product && $this->product->inStock();
    }

    /**
     * Verifica se o produto está fora de estoque
     */
    public function isOutOfStock(): bool
    {
        return $this->product && $this->product->outOfStock();
    }

    /**
     * Verifica se o produto tem desconto
     */
    public function hasDiscount(): bool
    {
        return $this->product && $this->product->hasDiscount();
    }

    /**
     * Obtém o preço total do item
     */
    public function getTotalPriceAttribute(): float
    {
        return $this->quantity * $this->product->price;
    }

    /**
     * Obtém o preço de comparação total
     */
    public function getTotalComparePriceAttribute(): ?float
    {
        if (!$this->product->compare_price) {
            return null;
        }

        return $this->quantity * $this->product->compare_price;
    }

    /**
     * Obtém o valor economizado
     */
    public function getSavingsAttribute(): ?float
    {
        if (!$this->hasDiscount()) {
            return null;
        }

        return $this->total_compare_price - $this->total_price;
    }

    /**
     * Obtém a porcentagem de desconto
     */
    public function getDiscountPercentageAttribute(): ?float
    {
        if (!$this->hasDiscount()) {
            return null;
        }

        return $this->product->getDiscountPercentage();
    }

    /**
     * Obtém o status do estoque
     */
    public function getStockStatusAttribute(): string
    {
        if ($this->isInStock()) {
            return 'in_stock';
        }

        return 'out_of_stock';
    }

    /**
     * Obtém o status do produto
     */
    public function getStatusAttribute(): string
    {
        if (!$this->product->is_active) {
            return 'inactive';
        }

        if ($this->isOutOfStock()) {
            return 'out_of_stock';
        }

        if ($this->hasDiscount()) {
            return 'on_sale';
        }

        return 'active';
    }

    /**
     * Obtém dados formatados para exibição
     */
    public function getFormattedDataAttribute(): array
    {
        return [
            'id' => $this->id,
            'product' => [
                'id' => $this->product->id,
                'name' => $this->product->name,
                'slug' => $this->product->slug,
                'sku' => $this->product->sku,
                'price' => $this->product->price,
                'formatted_price' => $this->product->getFormattedPrice(),
                'compare_price' => $this->product->compare_price,
                'formatted_compare_price' => $this->product->compare_price ? $this->product->getFormattedComparePrice() : null,
                'image' => $this->product->images->first() ? $this->product->images->first()->path : null,
                'is_active' => $this->product->is_active,
                'stock_quantity' => $this->product->stock_quantity,
                'in_stock' => $this->isInStock(),
                'has_discount' => $this->hasDiscount(),
                'discount_percentage' => $this->discount_percentage,
                'category' => $this->product->category ? [
                    'id' => $this->product->category->id,
                    'name' => $this->product->category->name,
                    'slug' => $this->product->category->slug
                ] : null,
                'brand' => $this->product->brand ? [
                    'id' => $this->product->brand->id,
                    'name' => $this->product->brand->name,
                    'slug' => $this->product->brand->slug
                ] : null
            ],
            'quantity' => $this->quantity,
            'notes' => $this->notes,
            'added_at' => $this->added_at->format('d/m/Y H:i'),
            'total_price' => $this->total_price,
            'formatted_total_price' => 'R$ ' . number_format($this->total_price, 2, ',', '.'),
            'savings' => $this->savings,
            'formatted_savings' => $this->savings ? 'R$ ' . number_format($this->savings, 2, ',', '.') : null,
            'status' => $this->status,
            'stock_status' => $this->stock_status
        ];
    }

    /**
     * Atualiza quantidade
     */
    public function updateQuantity(int $quantity): bool
    {
        if ($quantity <= 0) {
            return false;
        }

        $this->quantity = $quantity;
        return $this->save();
    }

    /**
     * Incrementa quantidade
     */
    public function incrementQuantity(int $amount = 1): bool
    {
        $this->quantity += $amount;
        return $this->save();
    }

    /**
     * Decrementa quantidade
     */
    public function decrementQuantity(int $amount = 1): bool
    {
        if ($this->quantity - $amount <= 0) {
            return false;
        }

        $this->quantity -= $amount;
        return $this->save();
    }

    /**
     * Move para outra wishlist
     */
    public function moveTo(Wishlist $wishlist): bool
    {
        if ($this->wishlist_id === $wishlist->id) {
            return false;
        }

        // Verifica se já existe na wishlist destino
        $existingItem = $wishlist->items()->where('product_id', $this->product_id)->first();

        if ($existingItem) {
            $existingItem->quantity += $this->quantity;
            $existingItem->save();
        } else {
            $this->wishlist_id = $wishlist->id;
            $this->save();
        }

        $this->delete();

        return true;
    }

    /**
     * Duplica item
     */
    public function duplicate(): WishlistItem
    {
        $newItem = $this->replicate();
        $newItem->save();

        return $newItem;
    }

    /**
     * Verifica se pode ser adicionado ao carrinho
     */
    public function canAddToCart(): bool
    {
        return $this->product && $this->product->is_active && $this->isInStock();
    }

    /**
     * Adiciona ao carrinho
     */
    public function addToCart(int $quantity = null): bool
    {
        if (!$this->canAddToCart()) {
            return false;
        }

        $cart = Cart::getCurrent();
        $cart->addProduct($this->product_id, $quantity ?: $this->quantity);

        return true;
    }

    /**
     * Compartilha item
     */
    public function share(): string
    {
        return $this->wishlist->share() . "#item-{$this->id}";
    }

    /**
     * Obtém tempo desde que foi adicionado
     */
    public function getTimeSinceAddedAttribute(): string
    {
        return $this->added_at->diffForHumans();
    }

    /**
     * Scopes
     */

    public function scopeInStock($query)
    {
        return $query->whereHas('product', function ($q) {
            $q->where('stock_quantity', '>', 0);
        });
    }

    public function scopeOutOfStock($query)
    {
        return $query->whereHas('product', function ($q) {
            $q->where('stock_quantity', '=', 0);
        });
    }

    public function scopeOnSale($query)
    {
        return $query->whereHas('product', function ($q) {
            $q->whereNotNull('compare_price')
              ->whereRaw('price < compare_price');
        });
    }

    public function scopeByProduct($query, int $productId)
    {
        return $query->where('product_id', $productId);
    }

    public function scopeByWishlist($query, int $wishlistId)
    {
        return $query->where('wishlist_id', $wishlistId);
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('added_at', '>=', Carbon::now()->subDays($days));
    }

    public function scopeWithProductDetails($query)
    {
        return $query->with(['product' => function ($q) {
            $q->with(['category', 'brand', 'images']);
        }]);
    }

    /**
     * Métodos estáticos
     */

    /**
     * Obtém itens mais adicionados
     */
    public static function getMostAdded(int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return static::select('product_id', \DB::raw('COUNT(*) as count'))
                    ->with('product')
                    ->groupBy('product_id')
                    ->orderBy('count', 'desc')
                    ->limit($limit)
                    ->get();
    }

    /**
     * Obtém estatísticas de itens
     */
    public static function getStats(): array
    {
        $totalItems = static::count();
        $uniqueProducts = static::distinct('product_id')->count();
        $averageQuantity = $totalItems > 0 ? static::avg('quantity') : 0;
        $mostAddedProduct = static::getMostAdded(1)->first();

        return [
            'total_items' => $totalItems,
            'unique_products' => $uniqueProducts,
            'average_quantity' => round($averageQuantity, 2),
            'most_added_product' => $mostAddedProduct
        ];
    }

    /**
     * Obtém itens por período
     */
    public static function getByDateRange(Carbon $startDate, Carbon $endDate): \Illuminate\Database\Eloquent\Collection
    {
        return static::whereBetween('added_at', [$startDate, $endDate])
                    ->with('product')
                    ->orderBy('added_at', 'desc')
                    ->get();
    }

    /**
     * Obtém produtos mais desejados por categoria
     */
    public static function getMostWishedByCategory(int $categoryId, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return static::select('wishlist_items.product_id', \DB::raw('COUNT(*) as count'))
                    ->join('products', 'wishlist_items.product_id', '=', 'products.id')
                    ->where('products.category_id', $categoryId)
                    ->groupBy('wishlist_items.product_id')
                    ->orderBy('count', 'desc')
                    ->limit($limit)
                    ->with('product')
                    ->get();
    }

    /**
     * Obtém produtos mais desejados por marca
     */
    public static function getMostWishedByBrand(int $brandId, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return static::select('wishlist_items.product_id', \DB::raw('COUNT(*) as count'))
                    ->join('products', 'wishlist_items.product_id', '=', 'products.id')
                    ->where('products.brand_id', $brandId)
                    ->groupBy('wishlist_items.product_id')
                    ->orderBy('count', 'desc')
                    ->limit($limit)
                    ->with('product')
                    ->get();
    }

    /**
     * Obtém tendências de wishlist
     */
    public static function getTrends(int $days = 7): array
    {
        $endDate = Carbon::now();
        $startDate = Carbon::now()->subDays($days);

        $currentPeriod = static::whereBetween('created_at', [$startDate, $endDate])->count();
        $previousPeriod = static::whereBetween('created_at', [$startDate->subDays($days), $startDate])->count();

        $trend = $previousPeriod > 0 ? (($currentPeriod - $previousPeriod) / $previousPeriod) * 100 : 0;

        return [
            'current_period' => $currentPeriod,
            'previous_period' => $previousPeriod,
            'trend' => round($trend, 2),
            'trend_direction' => $trend > 0 ? 'up' : ($trend < 0 ? 'down' : 'stable')
        ];
    }

    /**
     * Exporta item para array
     */
    public function toArray(): array
    {
        $array = parent::toArray();

        $array['product'] = $this->product;
        $array['total_price'] = $this->total_price;
        $array['total_compare_price'] = $this->total_compare_price;
        $array['savings'] = $this->savings;
        $array['discount_percentage'] = $this->discount_percentage;
        $array['status'] = $this->status;
        $array['stock_status'] = $this->stock_status;
        $array['time_since_added'] = $this->time_since_added;
        $array['formatted_data'] = $this->formatted_data;

        return $array;
    }

    /**
     * Exporta item para JSON
     */
    public function toJson($options = 0): string
    {
        return json_encode($this->toArray(), $options);
    }
}