<?php

namespace SupernovaCorp\LaravelEcommerceStore\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class Wishlist extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'customer_id',
        'name',
        'description',
        'is_public',
        'is_default',
        'sort_order',
        'meta'
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'is_default' => 'boolean',
        'meta' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    protected $attributes = [
        'is_public' => false,
        'is_default' => false,
        'sort_order' => 0
    ];

    /**
     * Boot do modelo
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($wishlist) {
            if ($wishlist->is_default) {
                // Remove default de outras wishlists do mesmo cliente
                static::where('customer_id', $wishlist->customer_id)
                      ->where('is_default', true)
                      ->update(['is_default' => false]);
            }
        });

        static::updating(function ($wishlist) {
            if ($wishlist->is_default) {
                // Remove default de outras wishlists do mesmo cliente
                static::where('customer_id', $wishlist->customer_id)
                      ->where('id', '!=', $wishlist->id)
                      ->where('is_default', true)
                      ->update(['is_default' => false]);
            }
        });
    }

    /**
     * Cliente proprietário da wishlist
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Itens da wishlist
     */
    public function items(): HasMany
    {
        return $this->hasMany(WishlistItem::class)->orderBy('sort_order')->orderBy('created_at', 'desc');
    }

    /**
     * Verifica se a wishlist pertence ao usuário autenticado
     */
    public function belongsToAuthUser(): bool
    {
        if (!Auth::check()) {
            return false;
        }

        return $this->customer_id === Auth::id();
    }

    /**
     * Verifica se a wishlist é pública
     */
    public function isPublic(): bool
    {
        return $this->is_public;
    }

    /**
     * Verifica se a wishlist é padrão
     */
    public function isDefault(): bool
    {
        return $this->is_default;
    }

    /**
     * Obtém o número total de itens
     */
    public function getTotalItemsCountAttribute(): int
    {
        return $this->items()->count();
    }

    /**
     * Obtém o valor total dos produtos
     */
    public function getTotalValueAttribute(): float
    {
        return $this->items()
                    ->join('products', 'wishlist_items.product_id', '=', 'products.id')
                    ->sum(\DB::raw('wishlist_items.quantity * products.price'));
    }

    /**
     * Obtém produtos em promoção
     */
    public function getOnSaleItemsAttribute(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->items()
                    ->join('products', 'wishlist_items.product_id', '=', 'products.id')
                    ->whereNotNull('products.compare_price')
                    ->whereRaw('products.price < products.compare_price')
                    ->get();
    }

    /**
     * Obtém produtos em estoque
     */
    public function getInStockItemsAttribute(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->items()
                    ->join('products', 'wishlist_items.product_id', '=', 'products.id')
                    ->where('products.stock_quantity', '>', 0)
                    ->get();
    }

    /**
     * Obtém produtos fora de estoque
     */
    public function getOutOfStockItemsAttribute(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->items()
                    ->join('products', 'wishlist_items.product_id', '=', 'products.id')
                    ->where('products.stock_quantity', '=', 0)
                    ->get();
    }

    /**
     * Adiciona produto à wishlist
     */
    public function addProduct(int $productId, int $quantity = 1, string $notes = null): WishlistItem
    {
        $item = $this->items()->firstOrNew([
            'product_id' => $productId
        ]);

        if (!$item->exists) {
            $item->quantity = $quantity;
            $item->notes = $notes;
            $item->added_at = Carbon::now();
            $item->save();
        } else {
            $item->increment('quantity', $quantity);
            if ($notes) {
                $item->notes = $notes;
                $item->save();
            }
        }

        return $item;
    }

    /**
     * Remove produto da wishlist
     */
    public function removeProduct(int $productId): bool
    {
        return $this->items()->where('product_id', $productId)->delete() > 0;
    }

    /**
     * Verifica se produto está na wishlist
     */
    public function hasProduct(int $productId): bool
    {
        return $this->items()->where('product_id', $productId)->exists();
    }

    /**
     * Obtém item específico
     */
    public function getItem(int $productId): ?WishlistItem
    {
        return $this->items()->where('product_id', $productId)->first();
    }

    /**
     * Limpa wishlist
     */
    public function clear(): bool
    {
        return $this->items()->delete() > 0;
    }

    /**
     * Move item para outra wishlist
     */
    public function moveItemTo(int $productId, Wishlist $targetWishlist): bool
    {
        $item = $this->getItem($productId);

        if (!$item) {
            return false;
        }

        // Remove da wishlist atual
        $this->removeProduct($productId);

        // Adiciona à wishlist destino
        $targetWishlist->addProduct($productId, $item->quantity, $item->notes);

        return true;
    }

    /**
     * Duplica wishlist
     */
    public function duplicate(string $newName = null): Wishlist
    {
        $newWishlist = $this->replicate();
        $newWishlist->name = $newName ?: $this->name . ' (Cópia)';
        $newWishlist->is_default = false;
        $newWishlist->save();

        // Duplica itens
        foreach ($this->items as $item) {
            $newWishlist->addProduct($item->product_id, $item->quantity, $item->notes);
        }

        return $newWishlist;
    }

    /**
     * Compartilha wishlist
     */
    public function share(): string
    {
        $this->update(['is_public' => true]);

        return route('wishlist.shared', ['token' => $this->generateShareToken()]);
    }

    /**
     * Gera token para compartilhamento
     */
    public function generateShareToken(): string
    {
        return encrypt([
            'wishlist_id' => $this->id,
            'expires_at' => Carbon::now()->addDays(30)->timestamp
        ]);
    }

    /**
     * Obtém wishlist por token de compartilhamento
     */
    public static function findByShareToken(string $token): ?Wishlist
    {
        try {
            $data = decrypt($token);

            if ($data['expires_at'] < Carbon::now()->timestamp) {
                return null;
            }

            return static::find($data['wishlist_id']);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Obtém estatísticas da wishlist
     */
    public function getStatsAttribute(): array
    {
        $items = $this->items()->with('product')->get();

        return [
            'total_items' => $items->count(),
            'total_value' => $items->sum(fn($item) => $item->quantity * $item->product->price),
            'on_sale_items' => $items->filter(fn($item) => $item->product->hasDiscount())->count(),
            'in_stock_items' => $items->filter(fn($item) => $item->product->inStock())->count(),
            'out_of_stock_items' => $items->filter(fn($item) => $item->product->outOfStock())->count(),
            'average_price' => $items->avg(fn($item) => $item->product->price),
            'categories_count' => $items->pluck('product.category_id')->unique()->count(),
            'brands_count' => $items->pluck('product.brand_id')->unique()->count()
        ];
    }

    /**
     * Scopes
     */

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopeByCustomer($query, int $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopeWithItems($query)
    {
        return $query->with('items.product');
    }

    public function scopeWithStats($query)
    {
        return $query->withCount('items');
    }

    /**
     * Métodos estáticos
     */

    /**
     * Obtém wishlist padrão do cliente
     */
    public static function getDefaultForCustomer(int $customerId): Wishlist
    {
        $wishlist = static::where('customer_id', $customerId)
                         ->where('is_default', true)
                         ->first();

        if (!$wishlist) {
            $wishlist = static::create([
                'customer_id' => $customerId,
                'name' => 'Lista de Desejos',
                'is_default' => true
            ]);
        }

        return $wishlist;
    }

    /**
     * Obtém todas as wishlists do cliente
     */
    public static function getForCustomer(int $customerId): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('customer_id', $customerId)
                    ->orderBy('is_default', 'desc')
                    ->orderBy('sort_order')
                    ->orderBy('created_at', 'desc')
                    ->get();
    }

    /**
     * Obtém wishlists públicas
     */
    public static function getPublicWishlists(int $limit = 20): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('is_public', true)
                    ->with('customer')
                    ->withCount('items')
                    ->orderBy('created_at', 'desc')
                    ->limit($limit)
                    ->get();
    }

    /**
     * Obtém produtos mais adicionados a wishlists
     */
    public static function getMostWishedProducts(int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return Product::select('products.*')
                     ->join('wishlist_items', 'products.id', '=', 'wishlist_items.product_id')
                     ->join('wishlists', 'wishlist_items.wishlist_id', '=', 'wishlists.id')
                     ->where('wishlists.is_public', true)
                     ->groupBy('products.id')
                     ->orderByRaw('COUNT(*) DESC')
                     ->limit($limit)
                     ->get();
    }

    /**
     * Obtém estatísticas gerais de wishlists
     */
    public static function getGlobalStats(): array
    {
        $totalWishlists = static::count();
        $publicWishlists = static::where('is_public', true)->count();
        $totalItems = \DB::table('wishlist_items')->count();
        $averageItemsPerWishlist = $totalWishlists > 0 ? $totalItems / $totalWishlists : 0;

        return [
            'total_wishlists' => $totalWishlists,
            'public_wishlists' => $publicWishlists,
            'total_items' => $totalItems,
            'average_items_per_wishlist' => round($averageItemsPerWishlist, 2),
            'most_wished_product' => static::getMostWishedProducts(1)->first()
        ];
    }

    /**
     * Exporta wishlist para array
     */
    public function toArray(): array
    {
        $array = parent::toArray();

        $array['total_items'] = $this->total_items_count;
        $array['total_value'] = $this->total_value;
        $array['stats'] = $this->stats;

        return $array;
    }

    /**
     * Exporta wishlist para JSON
     */
    public function toJson($options = 0): string
    {
        return json_encode($this->toArray(), $options);
    }
}