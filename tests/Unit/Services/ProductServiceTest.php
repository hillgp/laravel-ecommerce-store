<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\ProductService;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Review;
use App\Services\CacheService;
use Mockery;

class ProductServiceTest extends TestCase
{
    use RefreshDatabase;

    private ProductService $productService;
    private CacheService $cacheService;
    private Product $product;
    private Category $category;
    private Brand $brand;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cacheService = Mockery::mock(CacheService::class);
        $this->productService = new ProductService($this->cacheService);

        $this->category = Category::factory()->create();
        $this->brand = Brand::factory()->create();

        $this->product = Product::factory()->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'name' => 'Produto de Teste',
            'slug' => 'produto-de-teste',
            'sku' => 'TEST-001',
            'price' => 99.90,
            'is_active' => true
        ]);
    }

    public function test_product_service_can_get_featured_products()
    {
        $this->cacheService->shouldReceive('getFeaturedProducts')
            ->once()
            ->andReturn(collect([$this->product]));

        $featuredProducts = $this->productService->getFeaturedProducts(10);

        $this->assertCount(1, $featuredProducts);
        $this->assertTrue($featuredProducts->contains($this->product));
    }

    public function test_product_service_can_get_products_by_category()
    {
        $this->cacheService->shouldReceive('getProductsByCategory')
            ->with($this->category->id, 10)
            ->once()
            ->andReturn(collect([$this->product]));

        $products = $this->productService->getProductsByCategory($this->category->id, 10);

        $this->assertCount(1, $products);
        $this->assertTrue($products->contains($this->product));
    }

    public function test_product_service_can_get_products_by_brand()
    {
        $this->cacheService->shouldReceive('getProductsByBrand')
            ->with($this->brand->id, 10)
            ->once()
            ->andReturn(collect([$this->product]));

        $products = $this->productService->getProductsByBrand($this->brand->id, 10);

        $this->assertCount(1, $products);
        $this->assertTrue($products->contains($this->product));
    }

    public function test_product_service_can_search_products()
    {
        $this->cacheService->shouldReceive('searchProducts')
            ->with('Produto de Teste', 10)
            ->once()
            ->andReturn(collect([$this->product]));

        $searchResults = $this->productService->searchProducts('Produto de Teste', 10);

        $this->assertCount(1, $searchResults);
        $this->assertTrue($searchResults->contains($this->product));
    }

    public function test_product_service_can_get_product_with_reviews()
    {
        $review = Review::factory()->create([
            'product_id' => $this->product->id,
            'rating' => 5
        ]);

        $this->cacheService->shouldReceive('getProductWithReviews')
            ->with($this->product->id)
            ->once()
            ->andReturn($this->product->load('reviews'));

        $productWithReviews = $this->productService->getProductWithReviews($this->product->id);

        $this->assertTrue($productWithReviews->relationLoaded('reviews'));
        $this->assertCount(1, $productWithReviews->reviews);
    }

    public function test_product_service_can_get_related_products()
    {
        $relatedProduct = Product::factory()->create([
            'category_id' => $this->category->id,
            'is_active' => true
        ]);

        $this->cacheService->shouldReceive('getRelatedProducts')
            ->with($this->product->id, 5)
            ->once()
            ->andReturn(collect([$relatedProduct]));

        $relatedProducts = $this->productService->getRelatedProducts($this->product->id, 5);

        $this->assertCount(1, $relatedProducts);
        $this->assertTrue($relatedProducts->contains($relatedProduct));
    }

    public function test_product_service_can_get_similar_products()
    {
        $similarProduct = Product::factory()->create([
            'brand_id' => $this->brand->id,
            'is_active' => true
        ]);

        $this->cacheService->shouldReceive('getSimilarProducts')
            ->with($this->product->id, 5)
            ->once()
            ->andReturn(collect([$similarProduct]));

        $similarProducts = $this->productService->getSimilarProducts($this->product->id, 5);

        $this->assertCount(1, $similarProducts);
        $this->assertTrue($similarProducts->contains($similarProduct));
    }

    public function test_product_service_can_get_recent_products()
    {
        $this->cacheService->shouldReceive('getRecentProducts')
            ->with(10)
            ->once()
            ->andReturn(collect([$this->product]));

        $recentProducts = $this->productService->getRecentProducts(10);

        $this->assertCount(1, $recentProducts);
        $this->assertTrue($recentProducts->contains($this->product));
    }

    public function test_product_service_can_get_discounted_products()
    {
        $discountedProduct = Product::factory()->create([
            'price' => 80,
            'compare_price' => 100,
            'is_active' => true
        ]);

        $this->cacheService->shouldReceive('getDiscountedProducts')
            ->with(10)
            ->once()
            ->andReturn(collect([$discountedProduct]));

        $discountedProducts = $this->productService->getDiscountedProducts(10);

        $this->assertCount(1, $discountedProducts);
        $this->assertTrue($discountedProducts->contains($discountedProduct));
    }

    public function test_product_service_can_get_products_on_sale()
    {
        $saleProduct = Product::factory()->create([
            'price' => 75,
            'compare_price' => 100,
            'is_active' => true
        ]);

        $this->cacheService->shouldReceive('getProductsOnSale')
            ->with(10)
            ->once()
            ->andReturn(collect([$saleProduct]));

        $saleProducts = $this->productService->getProductsOnSale(10);

        $this->assertCount(1, $saleProducts);
        $this->assertTrue($saleProducts->contains($saleProduct));
    }

    public function test_product_service_can_get_best_sellers()
    {
        $this->cacheService->shouldReceive('getBestSellers')
            ->with(10)
            ->once()
            ->andReturn(collect([$this->product]));

        $bestSellers = $this->productService->getBestSellers(10);

        $this->assertCount(1, $bestSellers);
        $this->assertTrue($bestSellers->contains($this->product));
    }

    public function test_product_service_can_get_new_arrivals()
    {
        $this->cacheService->shouldReceive('getNewArrivals')
            ->with(10)
            ->once()
            ->andReturn(collect([$this->product]));

        $newArrivals = $this->productService->getNewArrivals(10);

        $this->assertCount(1, $newArrivals);
        $this->assertTrue($newArrivals->contains($this->product));
    }

    public function test_product_service_can_get_popular_products()
    {
        $this->cacheService->shouldReceive('getPopularProducts')
            ->with(10)
            ->once()
            ->andReturn(collect([$this->product]));

        $popularProducts = $this->productService->getPopularProducts(10);

        $this->assertCount(1, $popularProducts);
        $this->assertTrue($popularProducts->contains($this->product));
    }

    public function test_product_service_can_get_products_by_price_range()
    {
        $this->cacheService->shouldReceive('getProductsByPriceRange')
            ->with(50, 150, 10)
            ->once()
            ->andReturn(collect([$this->product]));

        $products = $this->productService->getProductsByPriceRange(50, 150, 10);

        $this->assertCount(1, $products);
        $this->assertTrue($products->contains($this->product));
    }

    public function test_product_service_can_get_products_by_rating()
    {
        $this->cacheService->shouldReceive('getProductsByRating')
            ->with(4.0, 10)
            ->once()
            ->andReturn(collect([$this->product]));

        $products = $this->productService->getProductsByRating(4.0, 10);

        $this->assertCount(1, $products);
        $this->assertTrue($products->contains($this->product));
    }

    public function test_product_service_can_get_products_by_tag()
    {
        $this->product->update(['tags' => ['teste', 'produto']]);

        $this->cacheService->shouldReceive('getProductsByTag')
            ->with('teste', 10)
            ->once()
            ->andReturn(collect([$this->product]));

        $products = $this->productService->getProductsByTag('teste', 10);

        $this->assertCount(1, $products);
        $this->assertTrue($products->contains($this->product));
    }

    public function test_product_service_can_get_products_by_tags()
    {
        $this->product->update(['tags' => ['teste', 'produto']]);

        $this->cacheService->shouldReceive('getProductsByTags')
            ->with(['teste', 'produto'], 10)
            ->once()
            ->andReturn(collect([$this->product]));

        $products = $this->productService->getProductsByTags(['teste', 'produto'], 10);

        $this->assertCount(1, $products);
        $this->assertTrue($products->contains($this->product));
    }

    public function test_product_service_can_get_products_with_images()
    {
        $this->product->images()->create([
            'path' => 'images/test.jpg',
            'alt' => 'Imagem de teste'
        ]);

        $this->cacheService->shouldReceive('getProductsWithImages')
            ->with(10)
            ->once()
            ->andReturn(collect([$this->product]));

        $products = $this->productService->getProductsWithImages(10);

        $this->assertCount(1, $products);
        $this->assertTrue($products->contains($this->product));
    }

    public function test_product_service_can_get_products_with_reviews()
    {
        Review::factory()->create(['product_id' => $this->product->id]);

        $this->cacheService->shouldReceive('getProductsWithReviews')
            ->with(10)
            ->once()
            ->andReturn(collect([$this->product]));

        $products = $this->productService->getProductsWithReviews(10);

        $this->assertCount(1, $products);
        $this->assertTrue($products->contains($this->product));
    }

    public function test_product_service_can_get_products_with_discount()
    {
        $discountedProduct = Product::factory()->create([
            'price' => 80,
            'compare_price' => 100,
            'is_active' => true
        ]);

        $this->cacheService->shouldReceive('getProductsWithDiscount')
            ->with(10)
            ->once()
            ->andReturn(collect([$discountedProduct]));

        $products = $this->productService->getProductsWithDiscount(10);

        $this->assertCount(1, $products);
        $this->assertTrue($products->contains($discountedProduct));
    }

    public function test_product_service_can_get_products_in_stock()
    {
        $this->cacheService->shouldReceive('getProductsInStock')
            ->with(10)
            ->once()
            ->andReturn(collect([$this->product]));

        $products = $this->productService->getProductsInStock(10);

        $this->assertCount(1, $products);
        $this->assertTrue($products->contains($this->product));
    }

    public function test_product_service_can_get_products_out_of_stock()
    {
        $outOfStockProduct = Product::factory()->create([
            'stock_quantity' => 0,
            'is_active' => true
        ]);

        $this->cacheService->shouldReceive('getProductsOutOfStock')
            ->with(10)
            ->once()
            ->andReturn(collect([$outOfStockProduct]));

        $products = $this->productService->getProductsOutOfStock(10);

        $this->assertCount(1, $products);
        $this->assertTrue($products->contains($outOfStockProduct));
    }

    public function test_product_service_can_get_products_low_stock()
    {
        $lowStockProduct = Product::factory()->create([
            'stock_quantity' => 5,
            'min_stock' => 10,
            'is_active' => true
        ]);

        $this->cacheService->shouldReceive('getProductsLowStock')
            ->with(10)
            ->once()
            ->andReturn(collect([$lowStockProduct]));

        $products = $this->productService->getProductsLowStock(10);

        $this->assertCount(1, $products);
        $this->assertTrue($products->contains($lowStockProduct));
    }

    public function test_product_service_can_get_product_by_sku()
    {
        $this->cacheService->shouldReceive('getProductBySku')
            ->with($this->product->sku)
            ->once()
            ->andReturn($this->product);

        $product = $this->productService->getProductBySku($this->product->sku);

        $this->assertEquals($this->product->id, $product->id);
    }

    public function test_product_service_can_get_product_by_slug()
    {
        $this->cacheService->shouldReceive('getProductBySlug')
            ->with($this->product->slug)
            ->once()
            ->andReturn($this->product);

        $product = $this->productService->getProductBySlug($this->product->slug);

        $this->assertEquals($this->product->id, $product->id);
    }

    public function test_product_service_can_get_product_by_id()
    {
        $this->cacheService->shouldReceive('getProductById')
            ->with($this->product->id)
            ->once()
            ->andReturn($this->product);

        $product = $this->productService->getProductById($this->product->id);

        $this->assertEquals($this->product->id, $product->id);
    }

    public function test_product_service_can_invalidate_product_cache()
    {
        $this->cacheService->shouldReceive('invalidateProductCache')
            ->with($this->product->id)
            ->once();

        $this->productService->invalidateProductCache($this->product->id);

        // Verifica se o método foi chamado
        $this->assertTrue(true);
    }

    public function test_product_service_can_invalidate_all_products_cache()
    {
        $this->cacheService->shouldReceive('invalidateAllProductsCache')
            ->once();

        $this->productService->invalidateAllProductsCache();

        // Verifica se o método foi chamado
        $this->assertTrue(true);
    }

    public function test_product_service_can_clear_cache()
    {
        $this->cacheService->shouldReceive('clear')
            ->once();

        $this->productService->clearCache();

        // Verifica se o método foi chamado
        $this->assertTrue(true);
    }

    public function test_product_service_can_get_cache_stats()
    {
        $this->cacheService->shouldReceive('getStats')
            ->once()
            ->andReturn(['hits' => 100, 'misses' => 10]);

        $stats = $this->productService->getCacheStats();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('hits', $stats);
        $this->assertArrayHasKey('misses', $stats);
    }

    public function test_product_service_can_get_categories()
    {
        $this->cacheService->shouldReceive('getCategories')
            ->with(true)
            ->once()
            ->andReturn(collect([$this->category]));

        $categories = $this->productService->getCategories(true);

        $this->assertCount(1, $categories);
        $this->assertTrue($categories->contains($this->category));
    }

    public function test_product_service_can_get_brands()
    {
        $this->cacheService->shouldReceive('getBrands')
            ->with(true)
            ->once()
            ->andReturn(collect([$this->brand]));

        $brands = $this->productService->getBrands(true);

        $this->assertCount(1, $brands);
        $this->assertTrue($brands->contains($this->brand));
    }

    public function test_product_service_can_get_category_tree()
    {
        $this->cacheService->shouldReceive('getCategoryTree')
            ->once()
            ->andReturn([['id' => $this->category->id, 'name' => $this->category->name]]);

        $tree = $this->productService->getCategoryTree();

        $this->assertIsArray($tree);
        $this->assertCount(1, $tree);
    }

    public function test_product_service_can_get_brand_tree()
    {
        $this->cacheService->shouldReceive('getBrandTree')
            ->once()
            ->andReturn([['id' => $this->brand->id, 'name' => $this->brand->name]]);

        $tree = $this->productService->getBrandTree();

        $this->assertIsArray($tree);
        $this->assertCount(1, $tree);
    }

    public function test_product_service_can_get_product_filters()
    {
        $this->cacheService->shouldReceive('getProductFilters')
            ->once()
            ->andReturn([
                'categories' => [$this->category],
                'brands' => [$this->brand],
                'price_range' => ['min' => 10, 'max' => 1000]
            ]);

        $filters = $this->productService->getProductFilters();

        $this->assertIsArray($filters);
        $this->assertArrayHasKey('categories', $filters);
        $this->assertArrayHasKey('brands', $filters);
        $this->assertArrayHasKey('price_range', $filters);
    }

    public function test_product_service_can_get_product_suggestions()
    {
        $this->cacheService->shouldReceive('getProductSuggestions')
            ->with('produto', 5)
            ->once()
            ->andReturn(collect([$this->product]));

        $suggestions = $this->productService->getProductSuggestions('produto', 5);

        $this->assertCount(1, $suggestions);
        $this->assertTrue($suggestions->contains($this->product));
    }

    public function test_product_service_can_get_trending_products()
    {
        $this->cacheService->shouldReceive('getTrendingProducts')
            ->with(10)
            ->once()
            ->andReturn(collect([$this->product]));

        $trending = $this->productService->getTrendingProducts(10);

        $this->assertCount(1, $trending);
        $this->assertTrue($trending->contains($this->product));
    }

    public function test_product_service_can_get_recommended_products()
    {
        $this->cacheService->shouldReceive('getRecommendedProducts')
            ->with($this->product->id, 5)
            ->once()
            ->andReturn(collect([$this->product]));

        $recommended = $this->productService->getRecommendedProducts($this->product->id, 5);

        $this->assertCount(1, $recommended);
        $this->assertTrue($recommended->contains($this->product));
    }

    public function test_product_service_can_get_products_by_category_slug()
    {
        $this->cacheService->shouldReceive('getProductsByCategorySlug')
            ->with($this->category->slug, 10)
            ->once()
            ->andReturn(collect([$this->product]));

        $products = $this->productService->getProductsByCategorySlug($this->category->slug, 10);

        $this->assertCount(1, $products);
        $this->assertTrue($products->contains($this->product));
    }

    public function test_product_service_can_get_products_by_brand_slug()
    {
        $this->cacheService->shouldReceive('getProductsByBrandSlug')
            ->with($this->brand->slug, 10)
            ->once()
            ->andReturn(collect([$this->product]));

        $products = $this->productService->getProductsByBrandSlug($this->brand->slug, 10);

        $this->assertCount(1, $products);
        $this->assertTrue($products->contains($this->product));
    }

    public function test_product_service_can_get_products_by_category_and_brand()
    {
        $this->cacheService->shouldReceive('getProductsByCategoryAndBrand')
            ->with($this->category->id, $this->brand->id, 10)
            ->once()
            ->andReturn(collect([$this->product]));

        $products = $this->productService->getProductsByCategoryAndBrand(
            $this->category->id,
            $this->brand->id,
            10
        );

        $this->assertCount(1, $products);
        $this->assertTrue($products->contains($this->product));
    }

    public function test_product_service_can_get_products_by_price_range_and_category()
    {
        $this->cacheService->shouldReceive('getProductsByPriceRangeAndCategory')
            ->with(50, 150, $this->category->id, 10)
            ->once()
            ->andReturn(collect([$this->product]));

        $products = $this->productService->getProductsByPriceRangeAndCategory(
            50,
            150,
            $this->category->id,
            10
        );

        $this->assertCount(1, $products);
        $this->assertTrue($products->contains($this->product));
    }

    public function test_product_service_can_get_products_by_price_range_and_brand()
    {
        $this->cacheService->shouldReceive('getProductsByPriceRangeAndBrand')
            ->with(50, 150, $this->brand->id, 10)
            ->once()
            ->andReturn(collect([$this->product]));

        $products = $this->productService->getProductsByPriceRangeAndBrand(
            50,
            150,
            $this->brand->id,
            10
        );

        $this->assertCount(1, $products);
        $this->assertTrue($products->contains($this->product));
    }

    public function test_product_service_can_get_products_by_rating_and_category()
    {
        $this->cacheService->shouldReceive('getProductsByRatingAndCategory')
            ->with(4.0, $this->category->id, 10)
            ->once()
            ->andReturn(collect([$this->product]));

        $products = $this->productService->getProductsByRatingAndCategory(
            4.0,
            $this->category->id,
            10
        );

        $this->assertCount(1, $products);
        $this->assertTrue($products->contains($this->product));
    }

    public function test_product_service_can_get_products_by_rating_and_brand()
    {
        $this->cacheService->shouldReceive('getProductsByRatingAndBrand')
            ->with(4.0, $this->brand->id, 10)
            ->once()
            ->andReturn(collect([$this->product]));

        $products = $this->productService->getProductsByRatingAndBrand(
            4.0,
            $this->brand->id,
            10
        );

        $this->assertCount(1, $products);
        $this->assertTrue($products->contains($this->product));
    }

    public function test_product_service_can_get_products_by_tag_and_category()
    {
        $this->product->update(['tags' => ['teste']]);

        $this->cacheService->shouldReceive('getProductsByTagAndCategory')
            ->with('teste', $this->category->id, 10)
            ->once()
            ->andReturn(collect([$this->product]));

        $products = $this->productService->getProductsByTagAndCategory(
            'teste',
            $this->category->id,
            10
        );

        $this->assertCount(1, $products);
        $this->assertTrue($products->contains($this->product));
    }

    public function test_product_service_can_get_products_by_tag_and_brand()
    {
        $this->product->update(['tags' => ['teste']]);

        $this->cacheService->shouldReceive('getProductsByTagAndBrand')
            ->with('teste', $this->brand->id, 10)
            ->once()
            ->andReturn(collect([$this->product]));

        $products = $this->productService->getProductsByTagAndBrand(
            'teste',
            $this->brand->id,
            10
        );

        $this->assertCount(1, $products);
        $this->assertTrue($products->contains($this->product));
    }

    public function test_product_service_can_get_products_by_price_range_and_tag()
    {
        $this->product->update(['tags' => ['teste']]);

        $this->cacheService->shouldReceive('getProductsByPriceRangeAndTag')
            ->with(50, 150, 'teste', 10)
            ->once()
            ->andReturn(collect([$this->product]));

        $products = $this->productService->getProductsByPriceRangeAndTag(
            50,
            150,
            'teste',
            10
        );

        $this->assertCount(1, $products);
        $this->assertTrue($products->contains($this->product));
    }

    public function test_product_service_can_get_products_by_rating_and_tag()
    {
        $this->product->update(['tags' => ['teste']]);

        $this->cacheService->shouldReceive('getProductsByRatingAndTag')
            ->with(4.0, 'teste', 10)
            ->once()
            ->andReturn(collect([$this->product]));

        $products = $this->productService->getProductsByRatingAndTag(
            4.0,
            'teste',
            10
        );

        $this->assertCount(1, $products);
        $this->assertTrue($products->contains($this->product));
    }

    public function test_product_service_can_get_products_by_price_range_rating_and_category()
    {
        $this->cacheService->shouldReceive('getProductsByPriceRangeRatingAndCategory')
            ->with(50, 150, 4.0, $this->category->id, 10)
            ->once()
            ->andReturn(collect([$this->product]));

        $products = $this->productService->getProductsByPriceRangeRatingAndCategory(
            50,
            150,
            4.0,
            $this->category->id,
            10
        );

        $this->assertCount(1, $products);
        $this->assertTrue($products->contains($this->product));
    }

    public function test_product_service_can_get_products_by_price_range_rating_and_brand()
    {
        $this->cacheService->shouldReceive('getProductsByPriceRangeRatingAndBrand')
            ->with(50, 150, 4.0, $this->brand->id, 10)
            ->once()
            ->andReturn(collect([$this->product]));

        $products = $this->productService->getProductsByPriceRangeRatingAndBrand(
            50,
            150,
            4.0,
            $this->brand->id,
            10
        );

        $this->assertCount(1, $products);
        $this->assertTrue($products->contains($this->product));
    }

    public function test_product_service_can_get_products_by_price_range_rating_and_tag()
    {
        $this->product->update(['tags' => ['teste']]);

        $this->cacheService->shouldReceive('getProductsByPriceRangeRatingAndTag')
            ->with(50, 150, 4.0, 'teste', 10)
            ->once()
            ->andReturn(collect([$this->product]));

        $products = $this->productService->getProductsByPriceRangeRatingAndTag(
            50,
            150,
            4.0,
            'teste',
            10
        );

        $this->assertCount(1, $products);
        $this->assertTrue($products->contains($this->product));
    }

    public function test_product_service_can_get_products_by_price_range_brand_and_category()
    {
        $this->cacheService->shouldReceive('getProductsByPriceRangeBrandAndCategory')
            ->with(50, 150, $this->brand->id, $this->category->id, 10)
            ->once()
            ->andReturn(collect([$this->product]));

        $products = $this->productService->getProductsByPriceRangeBrandAndCategory(
            50,
            150,
            $this->brand->id,
            $this->category->id,
            10
        );

        $this->assertCount(1, $products);
        $this->assertTrue($products->contains($this->product));
    }

    public function test_product_service_can_get_products_by_price_range_tag_and_category()
    {
        $this->product->update(['tags' => ['teste']]);

        $this->cacheService->shouldReceive('getProductsByPriceRangeTagAndCategory')
            ->with(50, 150, 'teste', $this->category->id, 10)
            ->once()
            ->andReturn(collect([$this->product]));

        $products = $this->productService->getProductsByPriceRangeTagAndCategory(
            50,
            150,
            'teste',
            $this->category->id,
            10
        );

        $this->assertCount(1, $products);
        $this->assertTrue($products->contains($this->product));
    }

    public function test_product_service_can_get_products_by_price_range_tag_and_brand()
    {
        $this->product->update(['tags' => ['teste']]);

        $this->cacheService->shouldReceive('getProductsByPriceRangeTagAndBrand')
            ->with(50, 150, 'teste', $this->brand->id, 10)
            ->once()
            ->andReturn(collect([$this->product]));

        $products = $this->productService->getProductsByPriceRangeTagAndBrand(
            50,
            150,
            'teste',
            $this->brand->id,
            10
        );

        $this->assertCount(1, $products);
        $this->assertTrue($products->contains($this->product));
    }

    public function test_product_service_can_get_products_by_rating_brand_and_category()
    {
        $this->cacheService->shouldReceive('getProductsByRatingBrandAndCategory')
            ->with(4.0, $this->brand->id, $this->category->id, 10)
            ->once()
            ->andReturn(collect([$this->product]));

        $products = $this->productService->getProductsByRatingBrandAndCategory(
            4.0,
            $this->brand->id,
            $this->category->id,
            10
        );

        $this->assertCount(1, $products);
        $this->assertTrue($products->contains($this->product));
    }

    public function test_product_service_can_get_products_by_rating_tag_and_category()
    {
        $this->product->update(['tags' => ['teste']]);

        $this->cacheService->shouldReceive('getProductsByRatingTagAndCategory')
            ->with(4.0, 'teste', $this->category->id, 10)
            ->once()
            ->andReturn(collect([$this->product]));

        $products = $this->productService->getProductsByRatingTagAndCategory(
            4.0,
            'teste',
            $this->category->id,
            10
        );

        $this->assertCount(1, $products);
        $this->assertTrue($products->contains($this->product));
    }

    public function test_product_service_can_get_products_by_rating_tag_and_brand()
    {
        $this->product->update(['tags' => ['teste']]);

        $this->cacheService->shouldReceive('getProductsByRatingTagAndBrand')
            ->with(4.0, 'teste', $this->brand->id, 10)
            ->once()
            ->andReturn(collect([$this->product]));

        $products = $this->productService->getProductsByRatingTagAndBrand(
            4.0,
            'teste',
            $this->brand->id,
            10
        );

        $this->assertCount(1, $products);
        $this->assertTrue($products->contains($this->product));
    }

    public function test_product_service_can_get_products_by_tag_brand_and_category()
    {
        $this->product->update(['tags' => ['teste']]);

        $this->cacheService->shouldReceive('getProductsByTagBrandAndCategory')
            ->with('teste', $this->brand->id, $this->category->id, 10)
            ->once()
            ->andReturn(collect([$this->product]));

        $products = $this->productService->getProductsByTagBrandAndCategory(
            'teste',
            $this->brand->id,
            $this->category->id,
            10
        );

        $this->assertCount(1, $products);
        $this->assertTrue($products->contains($this->product));
    }

    public function test_product_service_can_get_products_by_price_range_rating_brand_and_category()
    {
        $this->cacheService->shouldReceive('getProductsByPriceRangeRatingBrandAndCategory')
            ->with(50, 150, 4.0, $this->brand->id, $this->category->id, 10)
            ->once()
            ->andReturn(collect([$this->product]));

        $products = $this->productService->getProductsByPriceRangeRatingBrandAndCategory(
            50,
            150,
            4.0,
            $this->brand->id,
            $this->category->id,
            10
        );

        $this->assertCount(1, $products);
        $this->assertTrue($products->contains($this->product));
    }

    public function test_product_service_can_get_products_by_price_range_rating_tag_and_category()
    {
        $this->product->update(['tags' => ['teste']]);

        $this->cacheService->shouldReceive('getProductsByPriceRangeRatingTagAndCategory')
            ->with(50, 150, 4.0, 'teste', $this->category->id, 10)
            ->once()
            ->andReturn(collect([$this->product]));

        $products = $this->productService->getProductsByPriceRangeRatingTagAndCategory(
            50,
            150,
            4.0,
            'teste',
            $this->category->id,
            10
        );

        $this->assertCount(1, $products);
        $this->assertTrue($products->contains($this->product));
    }

    public function test_product_service_can_get_products_by_price_range_rating_tag_and_brand()
    {
        $this->product->update(['tags' => ['teste']]);

        $this->cacheService->shouldReceive('getProductsByPriceRangeRatingTagAndBrand')
            ->with(50, 150, 4.0, 'teste', $this->brand->id, 10)
            ->once()
            ->andReturn(collect([$this->product]));

        $products = $this->productService->getProductsByPriceRangeRatingTagAndBrand(
            50,
            150,
            4.0,
            'teste',
            $this->brand->id,
            10
        );

        $this->assertCount(1, $products);
        $this->assertTrue($products->contains($this->product));
    }

    public function test_product_service_can_get_products_by_price_range_tag_brand_and_category()
    {
        $this->product->update(['tags' => ['teste']]);

        $this->cacheService->shouldReceive('getProductsByPriceRangeTagBrandAndCategory')
            ->with(50, 150, 'teste', $this->brand->id, $this->category->id, 10)
            ->once()
            ->andReturn(collect([$this->product]));

        $products = $this->productService->getProductsByPriceRangeTagBrandAndCategory(
            50,
            150,
            'teste',
            $this->brand->id,
            $this->category->id,
            10
        );

        $this->assertCount(1, $products);
        $this->assertTrue($products->contains($this->product));
    }

    public function test_product_service_can_get_products_by_rating_tag_brand_and_category()
    {
        $this->product->update(['tags' => ['teste']]);

        $this->cacheService->shouldReceive('getProductsByRatingTagBrandAndCategory')
            ->with(4.0, 'teste', $this->brand->id, $this->category->id, 10)
            ->once()
            ->andReturn(collect([$this->product]));

        $products = $this->productService->getProductsByRatingTagBrandAndCategory(
            4.0,
            'teste',
            $this->brand->id,
            $this->category->id,
            10
        );

        $this->assertCount(1, $products);
        $this->assertTrue($products->contains($this->product));
    }

    public function test_product_service_can_get_products_by_price_range_rating_tag_brand_and_category()
    {
        $this->product->update(['tags' => ['teste']]);

        $this->cacheService->shouldReceive('getProductsByPriceRangeRatingTagBrandAndCategory')
            ->with(50, 150, 4.0, 'teste', $this->brand->id, $this->category->id, 10)
            ->once()
            ->andReturn(collect([$this->product]));

        $products = $this->productService->getProductsByPriceRangeRatingTagBrandAndCategory(
            50,
            150,
            4.0,
            'teste',
            $this->brand->id,
            $this->category->id,
            10
        );

        $this->assertCount(1, $products);
        $this->assertTrue($products->contains($this->product));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}