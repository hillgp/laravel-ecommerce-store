<?php

namespace LaravelEcommerce\Store\Services;

use LaravelEcommerce\Store\Models\Product;
use LaravelEcommerce\Store\Models\Category;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;

class ProductService
{
    /**
     * Get all products with pagination.
     */
    public function getAllProducts(array $filters = [], int $perPage = 12): LengthAwarePaginator
    {
        $query = Product::query()->with(['category', 'images']);

        $this->applyFilters($query, $filters);

        return $query->paginate($perPage);
    }

    /**
     * Get featured products.
     */
    public function getFeaturedProducts(int $limit = 8): Collection
    {
        return Product::featured()
            ->active()
            ->inStock()
            ->with(['category', 'images'])
            ->limit($limit)
            ->get();
    }

    /**
     * Get products on sale.
     */
    public function getSaleProducts(int $limit = 8): Collection
    {
        return Product::onSale()
            ->active()
            ->inStock()
            ->with(['category', 'images'])
            ->limit($limit)
            ->get();
    }

    /**
     * Get new arrival products.
     */
    public function getNewArrivals(int $limit = 8): Collection
    {
        return Product::newArrivals()
            ->active()
            ->inStock()
            ->with(['category', 'images'])
            ->limit($limit)
            ->get();
    }

    /**
     * Get products by category.
     */
    public function getProductsByCategory(Category $category, array $filters = [], int $perPage = 12): LengthAwarePaginator
    {
        $query = $category->products()
            ->active()
            ->inStock()
            ->with(['images']);

        $this->applyFilters($query, $filters);

        return $query->paginate($perPage);
    }

    /**
     * Search products.
     */
    public function searchProducts(string $search, array $filters = [], int $perPage = 12): LengthAwarePaginator
    {
        $query = Product::search($search)
            ->active()
            ->inStock()
            ->with(['category', 'images']);

        $this->applyFilters($query, $filters);

        return $query->paginate($perPage);
    }

    /**
     * Get product by slug.
     */
    public function getProductBySlug(string $slug): ?Product
    {
        return Product::where('slug', $slug)
            ->active()
            ->with(['category', 'images', 'reviews', 'relatedProducts'])
            ->first();
    }

    /**
     * Get related products.
     */
    public function getRelatedProducts(Product $product, int $limit = 4): Collection
    {
        return $product->relatedProducts()
            ->active()
            ->inStock()
            ->with(['images'])
            ->limit($limit)
            ->get();
    }

    /**
     * Get cross-sell products.
     */
    public function getCrossSellProducts(Product $product, int $limit = 4): Collection
    {
        return $product->crossSellProducts()
            ->active()
            ->inStock()
            ->with(['images'])
            ->limit($limit)
            ->get();
    }

    /**
     * Get up-sell products.
     */
    public function getUpSellProducts(Product $product, int $limit = 4): Collection
    {
        return $product->upSellProducts()
            ->active()
            ->inStock()
            ->with(['images'])
            ->limit($limit)
            ->get();
    }

    /**
     * Create a new product.
     */
    public function createProduct(array $data): Product
    {
        $product = Product::create($data);

        if (isset($data['images'])) {
            $this->attachImages($product, $data['images']);
        }

        if (isset($data['categories'])) {
            $product->categories()->attach($data['categories']);
        }

        return $product->load(['category', 'images']);
    }

    /**
     * Update a product.
     */
    public function updateProduct(Product $product, array $data): Product
    {
        $product->update($data);

        if (isset($data['images'])) {
            $this->syncImages($product, $data['images']);
        }

        if (isset($data['categories'])) {
            $product->categories()->sync($data['categories']);
        }

        return $product->fresh(['category', 'images']);
    }

    /**
     * Delete a product.
     */
    public function deleteProduct(Product $product): bool
    {
        return $product->delete();
    }

    /**
     * Toggle product status.
     */
    public function toggleStatus(Product $product): Product
    {
        $product->update(['is_active' => !$product->is_active]);
        return $product->fresh();
    }

    /**
     * Update product stock.
     */
    public function updateStock(Product $product, int $quantity, string $operation = 'set'): Product
    {
        switch ($operation) {
            case 'add':
                $newQuantity = $product->quantity + $quantity;
                break;
            case 'subtract':
                $newQuantity = max(0, $product->quantity - $quantity);
                break;
            default:
                $newQuantity = $quantity;
        }

        $product->update(['quantity' => $newQuantity]);
        return $product->fresh();
    }

    /**
     * Get product statistics.
     */
    public function getProductStats(): array
    {
        return [
            'total' => Product::count(),
            'active' => Product::active()->count(),
            'inactive' => Product::where('is_active', false)->count(),
            'featured' => Product::featured()->count(),
            'on_sale' => Product::onSale()->count(),
            'new_arrivals' => Product::newArrivals()->count(),
            'in_stock' => Product::inStock()->count(),
            'out_of_stock' => Product::outOfStock()->count(),
            'low_stock' => Product::lowStock()->count(),
        ];
    }

    /**
     * Apply filters to query.
     */
    protected function applyFilters(Builder $query, array $filters): void
    {
        if (isset($filters['category'])) {
            $query->byCategory($filters['category']);
        }

        if (isset($filters['brand'])) {
            $query->byBrand($filters['brand']);
        }

        if (isset($filters['price_min'])) {
            $query->where('price', '>=', $filters['price_min']);
        }

        if (isset($filters['price_max'])) {
            $query->where('price', '<=', $filters['price_max']);
        }

        if (isset($filters['rating'])) {
            $query->where('rating', '>=', $filters['rating']);
        }

        if (isset($filters['featured'])) {
            $query->featured();
        }

        if (isset($filters['on_sale'])) {
            $query->onSale();
        }

        if (isset($filters['new_arrival'])) {
            $query->newArrivals();
        }

        if (isset($filters['in_stock'])) {
            $query->inStock();
        }

        if (isset($filters['sort'])) {
            $this->applySorting($query, $filters['sort']);
        }
    }

    /**
     * Apply sorting to query.
     */
    protected function applySorting(Builder $query, string $sort): void
    {
        switch ($sort) {
            case 'price_asc':
                $query->orderBy('price', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('price', 'desc');
                break;
            case 'name_asc':
                $query->orderBy('name', 'asc');
                break;
            case 'name_desc':
                $query->orderBy('name', 'desc');
                break;
            case 'newest':
                $query->orderBy('created_at', 'desc');
                break;
            case 'oldest':
                $query->orderBy('created_at', 'asc');
                break;
            case 'rating':
                $query->orderBy('rating', 'desc');
                break;
            case 'popular':
                $query->orderBy('view_count', 'desc');
                break;
            default:
                $query->orderBy('sort_order')->orderBy('created_at', 'desc');
        }
    }

    /**
     * Attach images to product.
     */
    protected function attachImages(Product $product, array $images): void
    {
        foreach ($images as $index => $image) {
            $product->images()->create([
                'url' => $image['url'],
                'thumbnail_url' => $image['thumbnail_url'] ?? null,
                'alt' => $image['alt'] ?? null,
                'sort_order' => $index,
            ]);
        }
    }

    /**
     * Sync images for product.
     */
    protected function syncImages(Product $product, array $images): void
    {
        $product->images()->delete();

        if (!empty($images)) {
            $this->attachImages($product, $images);
        }
    }
}