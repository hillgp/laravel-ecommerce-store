<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Review;
use App\Traits\HasImages;
use App\Traits\HasInventory;
use App\Traits\HasReviews;
use Carbon\Carbon;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;
    private Category $category;
    private Brand $brand;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = Category::factory()->create();
        $this->brand = Brand::factory()->create();

        $this->product = Product::factory()->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'name' => 'Produto de Teste',
            'slug' => 'produto-de-teste',
            'sku' => 'TEST-001',
            'price' => 99.90,
            'compare_price' => 129.90,
            'cost' => 50.00,
            'stock_quantity' => 100,
            'min_stock' => 10,
            'max_stock' => 1000,
            'is_active' => true,
            'is_featured' => false,
            'is_digital' => false,
            'weight' => 1.5,
            'length' => 20,
            'width' => 15,
            'height' => 5,
            'meta_title' => 'Produto de Teste - Loja Online',
            'meta_description' => 'Descrição do produto de teste para SEO',
            'tags' => ['teste', 'produto', 'laravel']
        ]);
    }

    public function test_product_belongs_to_category()
    {
        $this->assertInstanceOf(Category::class, $this->product->category);
        $this->assertEquals($this->category->id, $this->product->category->id);
    }

    public function test_product_belongs_to_brand()
    {
        $this->assertInstanceOf(Brand::class, $this->product->brand);
        $this->assertEquals($this->brand->id, $this->product->brand->id);
    }

    public function test_product_has_many_reviews()
    {
        $review = Review::factory()->create([
            'product_id' => $this->product->id,
            'rating' => 5,
            'comment' => 'Excelente produto!'
        ]);

        $this->assertInstanceOf(Review::class, $this->product->reviews->first());
        $this->assertEquals($review->id, $this->product->reviews->first()->id);
    }

    public function test_product_uses_has_images_trait()
    {
        $this->assertContains(HasImages::class, class_uses($this->product));
    }

    public function test_product_uses_has_inventory_trait()
    {
        $this->assertContains(HasInventory::class, class_uses($this->product));
    }

    public function test_product_uses_has_reviews_trait()
    {
        $this->assertContains(HasReviews::class, class_uses($this->product));
    }

    public function test_product_can_check_stock_status()
    {
        $this->assertTrue($this->product->inStock());
        $this->assertFalse($this->product->outOfStock());
        $this->assertFalse($this->product->lowStock());
    }

    public function test_product_can_calculate_discount_percentage()
    {
        $discount = $this->product->getDiscountPercentage();
        $expected = (($this->product->compare_price - $this->product->price) / $this->product->compare_price) * 100;

        $this->assertEquals($expected, $discount);
    }

    public function test_product_can_generate_slug()
    {
        $product = new Product(['name' => 'Produto com Slug Especial']);
        $product->generateSlug();

        $this->assertEquals('produto-com-slug-especial', $product->slug);
    }

    public function test_product_can_get_formatted_price()
    {
        $formattedPrice = $this->product->getFormattedPrice();
        $this->assertEquals('R$ 99,90', $formattedPrice);
    }

    public function test_product_can_get_formatted_compare_price()
    {
        $formattedPrice = $this->product->getFormattedComparePrice();
        $this->assertEquals('R$ 129,90', $formattedPrice);
    }

    public function test_product_can_get_profit_margin()
    {
        $margin = $this->product->getProfitMargin();
        $expected = (($this->product->price - $this->product->cost) / $this->product->price) * 100;

        $this->assertEquals($expected, $margin);
    }

    public function test_product_can_scope_active_products()
    {
        $activeProducts = Product::active()->get();
        $this->assertTrue($activeProducts->contains($this->product));
    }

    public function test_product_can_scope_featured_products()
    {
        $this->product->update(['is_featured' => true]);
        $featuredProducts = Product::featured()->get();

        $this->assertTrue($featuredProducts->contains($this->product));
    }

    public function test_product_can_scope_in_stock_products()
    {
        $inStockProducts = Product::inStock()->get();
        $this->assertTrue($inStockProducts->contains($this->product));
    }

    public function test_product_can_scope_by_category()
    {
        $productsByCategory = Product::byCategory($this->category->id)->get();
        $this->assertTrue($productsByCategory->contains($this->product));
    }

    public function test_product_can_scope_by_brand()
    {
        $productsByBrand = Product::byBrand($this->brand->id)->get();
        $this->assertTrue($productsByBrand->contains($this->product));
    }

    public function test_product_can_scope_by_price_range()
    {
        $productsInRange = Product::byPriceRange(50, 150)->get();
        $this->assertTrue($productsInRange->contains($this->product));
    }

    public function test_product_can_scope_search()
    {
        $searchResults = Product::search('Produto de Teste')->get();
        $this->assertTrue($searchResults->contains($this->product));
    }

    public function test_product_can_get_average_rating()
    {
        Review::factory()->create([
            'product_id' => $this->product->id,
            'rating' => 4
        ]);

        Review::factory()->create([
            'product_id' => $this->product->id,
            'rating' => 5
        ]);

        $averageRating = $this->product->getAverageRating();
        $this->assertEquals(4.5, $averageRating);
    }

    public function test_product_can_get_total_reviews_count()
    {
        Review::factory()->count(3)->create([
            'product_id' => $this->product->id
        ]);

        $totalReviews = $this->product->getTotalReviewsCount();
        $this->assertEquals(3, $totalReviews);
    }

    public function test_product_can_check_if_has_discount()
    {
        $this->assertTrue($this->product->hasDiscount());

        $productWithoutDiscount = Product::factory()->create([
            'price' => 100,
            'compare_price' => null
        ]);

        $this->assertFalse($productWithoutDiscount->hasDiscount());
    }

    public function test_product_can_get_stock_status_badge()
    {
        $badge = $this->product->getStockStatusBadge();
        $this->assertEquals('success', $badge['type']);
        $this->assertEquals('Em Estoque', $badge['text']);
    }

    public function test_product_can_get_seo_data()
    {
        $seoData = $this->product->getSeoData();

        $this->assertArrayHasKey('title', $seoData);
        $this->assertArrayHasKey('description', $seoData);
        $this->assertArrayHasKey('keywords', $seoData);
        $this->assertEquals($this->product->meta_title, $seoData['title']);
        $this->assertEquals($this->product->meta_description, $seoData['description']);
    }

    public function test_product_can_get_breadcrumb_data()
    {
        $breadcrumb = $this->product->getBreadcrumbData();

        $this->assertArrayHasKey('category', $breadcrumb);
        $this->assertArrayHasKey('product', $breadcrumb);
        $this->assertEquals($this->category->name, $breadcrumb['category']['name']);
        $this->assertEquals($this->product->name, $breadcrumb['product']['name']);
    }

    public function test_product_can_get_related_products()
    {
        $relatedCategory = Category::factory()->create();
        $relatedProducts = Product::factory()->count(3)->create([
            'category_id' => $relatedCategory->id,
            'is_active' => true
        ]);

        $this->product->update(['category_id' => $relatedCategory->id]);

        $related = $this->product->getRelatedProducts(2);

        $this->assertCount(2, $related);
        $this->assertFalse($related->contains($this->product));
    }

    public function test_product_can_get_similar_products()
    {
        $similarProducts = Product::factory()->count(3)->create([
            'category_id' => $this->category->id,
            'is_active' => true
        ]);

        $similar = $this->product->getSimilarProducts(2);

        $this->assertCount(2, $similar);
        $this->assertFalse($similar->contains($this->product));
    }

    public function test_product_can_get_recently_viewed()
    {
        $recentProducts = Product::factory()->count(5)->create(['is_active' => true]);
        $recentlyViewed = Product::getRecentlyViewed(3);

        $this->assertCount(3, $recentlyViewed);
    }

    public function test_product_can_get_popular_products()
    {
        $popularProducts = Product::factory()->count(3)->create(['is_active' => true]);
        $popular = Product::getPopularProducts(2);

        $this->assertCount(2, $popular);
    }

    public function test_product_can_get_best_sellers()
    {
        $bestSellers = Product::factory()->count(3)->create(['is_active' => true]);
        $bestSellersList = Product::getBestSellers(2);

        $this->assertCount(2, $bestSellersList);
    }

    public function test_product_can_get_new_arrivals()
    {
        $newProducts = Product::factory()->count(3)->create([
            'is_active' => true,
            'created_at' => Carbon::now()->subDays(5)
        ]);

        $newArrivals = Product::getNewArrivals(2);

        $this->assertCount(2, $newArrivals);
    }

    public function test_product_can_get_discounted_products()
    {
        $discountedProducts = Product::factory()->count(3)->create([
            'is_active' => true,
            'price' => 80,
            'compare_price' => 100
        ]);

        $discounted = Product::getDiscountedProducts(2);

        $this->assertCount(2, $discounted);
    }

    public function test_product_can_get_products_on_sale()
    {
        $saleProducts = Product::factory()->count(3)->create([
            'is_active' => true,
            'price' => 75,
            'compare_price' => 100
        ]);

        $onSale = Product::getProductsOnSale(2);

        $this->assertCount(2, $onSale);
    }

    public function test_product_can_get_featured_products()
    {
        $featuredProducts = Product::factory()->count(3)->create([
            'is_active' => true,
            'is_featured' => true
        ]);

        $featured = Product::getFeaturedProducts(2);

        $this->assertCount(2, $featured);
    }

    public function test_product_can_get_products_by_tag()
    {
        $taggedProducts = Product::factory()->count(3)->create([
            'is_active' => true,
            'tags' => ['tecnologia', 'inovação']
        ]);

        $byTag = Product::getProductsByTag('tecnologia', 2);

        $this->assertCount(2, $byTag);
    }

    public function test_product_can_get_products_by_price_range()
    {
        $priceRangeProducts = Product::factory()->count(3)->create([
            'is_active' => true,
            'price' => 150
        ]);

        $byPriceRange = Product::getProductsByPriceRange(100, 200, 2);

        $this->assertCount(2, $byPriceRange);
    }

    public function test_product_can_get_products_by_rating()
    {
        $highRatedProducts = Product::factory()->count(3)->create(['is_active' => true]);

        // Criar avaliações com rating alto
        foreach ($highRatedProducts as $product) {
            Review::factory()->create([
                'product_id' => $product->id,
                'rating' => 5
            ]);
        }

        $byRating = Product::getProductsByRating(4.5, 2);

        $this->assertCount(2, $byRating);
    }

    public function test_product_can_get_products_by_brand()
    {
        $brandProducts = Product::factory()->count(3)->create([
            'brand_id' => $this->brand->id,
            'is_active' => true
        ]);

        $byBrand = Product::getProductsByBrand($this->brand->id, 2);

        $this->assertCount(2, $byBrand);
    }

    public function test_product_can_get_products_by_category()
    {
        $categoryProducts = Product::factory()->count(3)->create([
            'category_id' => $this->category->id,
            'is_active' => true
        ]);

        $byCategory = Product::getProductsByCategory($this->category->id, 2);

        $this->assertCount(2, $byCategory);
    }

    public function test_product_can_get_products_with_images()
    {
        $productsWithImages = Product::factory()->count(3)->create(['is_active' => true]);

        // Adicionar imagens aos produtos
        foreach ($productsWithImages as $product) {
            $product->images()->create([
                'path' => 'images/products/test.jpg',
                'alt' => 'Imagem de teste',
                'sort_order' => 1
            ]);
        }

        $withImages = Product::getProductsWithImages(2);

        $this->assertCount(2, $withImages);
    }

    public function test_product_can_get_products_with_reviews()
    {
        $productsWithReviews = Product::factory()->count(3)->create(['is_active' => true]);

        // Adicionar avaliações aos produtos
        foreach ($productsWithReviews as $product) {
            Review::factory()->create([
                'product_id' => $product->id,
                'rating' => 4
            ]);
        }

        $withReviews = Product::getProductsWithReviews(2);

        $this->assertCount(2, $withReviews);
    }

    public function test_product_can_get_products_with_discount()
    {
        $discountedProducts = Product::factory()->count(3)->create([
            'is_active' => true,
            'price' => 80,
            'compare_price' => 100
        ]);

        $withDiscount = Product::getProductsWithDiscount(2);

        $this->assertCount(2, $withDiscount);
    }

    public function test_product_can_get_products_in_stock()
    {
        $inStockProducts = Product::factory()->count(3)->create([
            'is_active' => true,
            'stock_quantity' => 50
        ]);

        $inStock = Product::getProductsInStock(2);

        $this->assertCount(2, $inStock);
    }

    public function test_product_can_get_products_out_of_stock()
    {
        $outOfStockProducts = Product::factory()->count(3)->create([
            'is_active' => true,
            'stock_quantity' => 0
        ]);

        $outOfStock = Product::getProductsOutOfStock(2);

        $this->assertCount(2, $outOfStock);
    }

    public function test_product_can_get_products_low_stock()
    {
        $lowStockProducts = Product::factory()->count(3)->create([
            'is_active' => true,
            'stock_quantity' => 5,
            'min_stock' => 10
        ]);

        $lowStock = Product::getProductsLowStock(2);

        $this->assertCount(2, $lowStock);
    }

    public function test_product_can_get_products_by_sku()
    {
        $productBySku = Product::getProductBySku($this->product->sku);

        $this->assertInstanceOf(Product::class, $productBySku);
        $this->assertEquals($this->product->id, $productBySku->id);
    }

    public function test_product_can_get_products_by_slug()
    {
        $productBySlug = Product::getProductBySlug($this->product->slug);

        $this->assertInstanceOf(Product::class, $productBySlug);
        $this->assertEquals($this->product->id, $productBySlug->id);
    }

    public function test_product_can_get_products_by_name()
    {
        $productByName = Product::getProductByName($this->product->name);

        $this->assertInstanceOf(Product::class, $productByName);
        $this->assertEquals($this->product->id, $productByName->id);
    }

    public function test_product_can_get_products_by_id()
    {
        $productById = Product::getProductById($this->product->id);

        $this->assertInstanceOf(Product::class, $productById);
        $this->assertEquals($this->product->id, $productById->id);
    }

    public function test_product_can_get_products_by_category_slug()
    {
        $productsByCategorySlug = Product::getProductsByCategorySlug($this->category->slug, 2);

        $this->assertCount(1, $productsByCategorySlug);
        $this->assertTrue($productsByCategorySlug->contains($this->product));
    }

    public function test_product_can_get_products_by_brand_slug()
    {
        $productsByBrandSlug = Product::getProductsByBrandSlug($this->brand->slug, 2);

        $this->assertCount(1, $productsByBrandSlug);
        $this->assertTrue($productsByBrandSlug->contains($this->product));
    }

    public function test_product_can_get_products_by_tag_array()
    {
        $productsByTags = Product::getProductsByTags(['teste', 'produto'], 2);

        $this->assertCount(1, $productsByTags);
        $this->assertTrue($productsByTags->contains($this->product));
    }

    public function test_product_can_get_products_by_multiple_categories()
    {
        $category2 = Category::factory()->create();
        $productsMultiCategory = Product::factory()->count(3)->create([
            'category_id' => $category2->id,
            'is_active' => true
        ]);

        $productsByCategories = Product::getProductsByCategories([$this->category->id, $category2->id], 2);

        $this->assertCount(2, $productsByCategories);
    }

    public function test_product_can_get_products_by_multiple_brands()
    {
        $brand2 = Brand::factory()->create();
        $productsMultiBrand = Product::factory()->count(3)->create([
            'brand_id' => $brand2->id,
            'is_active' => true
        ]);

        $productsByBrands = Product::getProductsByBrands([$this->brand->id, $brand2->id], 2);

        $this->assertCount(2, $productsByBrands);
    }

    public function test_product_can_get_products_by_date_range()
    {
        $recentProducts = Product::factory()->count(3)->create([
            'is_active' => true,
            'created_at' => Carbon::now()->subDays(5)
        ]);

        $productsByDateRange = Product::getProductsByDateRange(
            Carbon::now()->subDays(10),
            Carbon::now(),
            2
        );

        $this->assertCount(2, $productsByDateRange);
    }

    public function test_product_can_get_products_by_price_range_and_category()
    {
        $productsByPriceAndCategory = Product::getProductsByPriceRangeAndCategory(
            50,
            150,
            $this->category->id,
            2
        );

        $this->assertCount(1, $productsByPriceAndCategory);
        $this->assertTrue($productsByPriceAndCategory->contains($this->product));
    }

    public function test_product_can_get_products_by_rating_and_category()
    {
        $productsByRatingAndCategory = Product::getProductsByRatingAndCategory(
            4.0,
            $this->category->id,
            2
        );

        $this->assertCount(1, $productsByRatingAndCategory);
        $this->assertTrue($productsByRatingAndCategory->contains($this->product));
    }

    public function test_product_can_get_products_by_brand_and_category()
    {
        $productsByBrandAndCategory = Product::getProductsByBrandAndCategory(
            $this->brand->id,
            $this->category->id,
            2
        );

        $this->assertCount(1, $productsByBrandAndCategory);
        $this->assertTrue($productsByBrandAndCategory->contains($this->product));
    }

    public function test_product_can_get_products_by_tag_and_category()
    {
        $productsByTagAndCategory = Product::getProductsByTagAndCategory(
            'teste',
            $this->category->id,
            2
        );

        $this->assertCount(1, $productsByTagAndCategory);
        $this->assertTrue($productsByTagAndCategory->contains($this->product));
    }

    public function test_product_can_get_products_by_price_range_and_brand()
    {
        $productsByPriceAndBrand = Product::getProductsByPriceRangeAndBrand(
            50,
            150,
            $this->brand->id,
            2
        );

        $this->assertCount(1, $productsByPriceAndBrand);
        $this->assertTrue($productsByPriceAndBrand->contains($this->product));
    }

    public function test_product_can_get_products_by_rating_and_brand()
    {
        $productsByRatingAndBrand = Product::getProductsByRatingAndBrand(
            4.0,
            $this->brand->id,
            2
        );

        $this->assertCount(1, $productsByRatingAndBrand);
        $this->assertTrue($productsByRatingAndBrand->contains($this->product));
    }

    public function test_product_can_get_products_by_tag_and_brand()
    {
        $productsByTagAndBrand = Product::getProductsByTagAndBrand(
            'teste',
            $this->brand->id,
            2
        );

        $this->assertCount(1, $productsByTagAndBrand);
        $this->assertTrue($productsByTagAndBrand->contains($this->product));
    }

    public function test_product_can_get_products_by_price_range_and_tag()
    {
        $productsByPriceAndTag = Product::getProductsByPriceRangeAndTag(
            50,
            150,
            'teste',
            2
        );

        $this->assertCount(1, $productsByPriceAndTag);
        $this->assertTrue($productsByPriceAndTag->contains($this->product));
    }

    public function test_product_can_get_products_by_rating_and_tag()
    {
        $productsByRatingAndTag = Product::getProductsByRatingAndTag(
            4.0,
            'teste',
            2
        );

        $this->assertCount(1, $productsByRatingAndTag);
        $this->assertTrue($productsByRatingAndTag->contains($this->product));
    }

    public function test_product_can_get_products_by_price_range_rating_and_category()
    {
        $productsByPriceRatingCategory = Product::getProductsByPriceRangeRatingAndCategory(
            50,
            150,
            4.0,
            $this->category->id,
            2
        );

        $this->assertCount(1, $productsByPriceRatingCategory);
        $this->assertTrue($productsByPriceRatingCategory->contains($this->product));
    }

    public function test_product_can_get_products_by_price_range_rating_and_brand()
    {
        $productsByPriceRatingBrand = Product::getProductsByPriceRangeRatingAndBrand(
            50,
            150,
            4.0,
            $this->brand->id,
            2
        );

        $this->assertCount(1, $productsByPriceRatingBrand);
        $this->assertTrue($productsByPriceRatingBrand->contains($this->product));
    }

    public function test_product_can_get_products_by_price_range_rating_and_tag()
    {
        $productsByPriceRatingTag = Product::getProductsByPriceRangeRatingAndTag(
            50,
            150,
            4.0,
            'teste',
            2
        );

        $this->assertCount(1, $productsByPriceRatingTag);
        $this->assertTrue($productsByPriceRatingTag->contains($this->product));
    }

    public function test_product_can_get_products_by_price_range_brand_and_category()
    {
        $productsByPriceBrandCategory = Product::getProductsByPriceRangeBrandAndCategory(
            50,
            150,
            $this->brand->id,
            $this->category->id,
            2
        );

        $this->assertCount(1, $productsByPriceBrandCategory);
        $this->assertTrue($productsByPriceBrandCategory->contains($this->product));
    }

    public function test_product_can_get_products_by_price_range_tag_and_category()
    {
        $productsByPriceTagCategory = Product::getProductsByPriceRangeTagAndCategory(
            50,
            150,
            'teste',
            $this->category->id,
            2
        );

        $this->assertCount(1, $productsByPriceTagCategory);
        $this->assertTrue($productsByPriceTagCategory->contains($this->product));
    }

    public function test_product_can_get_products_by_price_range_tag_and_brand()
    {
        $productsByPriceTagBrand = Product::getProductsByPriceRangeTagAndBrand(
            50,
            150,
            'teste',
            $this->brand->id,
            2
        );

        $this->assertCount(1, $productsByPriceTagBrand);
        $this->assertTrue($productsByPriceTagBrand->contains($this->product));
    }

    public function test_product_can_get_products_by_rating_brand_and_category()
    {
        $productsByRatingBrandCategory = Product::getProductsByRatingBrandAndCategory(
            4.0,
            $this->brand->id,
            $this->category->id,
            2
        );

        $this->assertCount(1, $productsByRatingBrandCategory);
        $this->assertTrue($productsByRatingBrandCategory->contains($this->product));
    }

    public function test_product_can_get_products_by_rating_tag_and_category()
    {
        $productsByRatingTagCategory = Product::getProductsByRatingTagAndCategory(
            4.0,
            'teste',
            $this->category->id,
            2
        );

        $this->assertCount(1, $productsByRatingTagCategory);
        $this->assertTrue($productsByRatingTagCategory->contains($this->product));
    }

    public function test_product_can_get_products_by_rating_tag_and_brand()
    {
        $productsByRatingTagBrand = Product::getProductsByRatingTagAndBrand(
            4.0,
            'teste',
            $this->brand->id,
            2
        );

        $this->assertCount(1, $productsByRatingTagBrand);
        $this->assertTrue($productsByRatingTagBrand->contains($this->product));
    }

    public function test_product_can_get_products_by_tag_brand_and_category()
    {
        $productsByTagBrandCategory = Product::getProductsByTagBrandAndCategory(
            'teste',
            $this->brand->id,
            $this->category->id,
            2
        );

        $this->assertCount(1, $productsByTagBrandCategory);
        $this->assertTrue($productsByTagBrandCategory->contains($this->product));
    }

    public function test_product_can_get_products_by_price_range_rating_brand_and_category()
    {
        $productsByPriceRatingBrandCategory = Product::getProductsByPriceRangeRatingBrandAndCategory(
            50,
            150,
            4.0,
            $this->brand->id,
            $this->category->id,
            2
        );

        $this->assertCount(1, $productsByPriceRatingBrandCategory);
        $this->assertTrue($productsByPriceRatingBrandCategory->contains($this->product));
    }

    public function test_product_can_get_products_by_price_range_rating_tag_and_category()
    {
        $productsByPriceRatingTagCategory = Product::getProductsByPriceRangeRatingTagAndCategory(
            50,
            150,
            4.0,
            'teste',
            $this->category->id,
            2
        );

        $this->assertCount(1, $productsByPriceRatingTagCategory);
        $this->assertTrue($productsByPriceRatingTagCategory->contains($this->product));
    }

    public function test_product_can_get_products_by_price_range_rating_tag_and_brand()
    {
        $productsByPriceRatingTagBrand = Product::getProductsByPriceRangeRatingTagAndBrand(
            50,
            150,
            4.0,
            'teste',
            $this->brand->id,
            2
        );

        $this->assertCount(1, $productsByPriceRatingTagBrand);
        $this->assertTrue($productsByPriceRatingTagBrand->contains($this->product));
    }

    public function test_product_can_get_products_by_price_range_tag_brand_and_category()
    {
        $productsByPriceTagBrandCategory = Product::getProductsByPriceRangeTagBrandAndCategory(
            50,
            150,
            'teste',
            $this->brand->id,
            $this->category->id,
            2
        );

        $this->assertCount(1, $productsByPriceTagBrandCategory);
        $this->assertTrue($productsByPriceTagBrandCategory->contains($this->product));
    }

    public function test_product_can_get_products_by_rating_tag_brand_and_category()
    {
        $productsByRatingTagBrandCategory = Product::getProductsByRatingTagBrandAndCategory(
            4.0,
            'teste',
            $this->brand->id,
            $this->category->id,
            2
        );

        $this->assertCount(1, $productsByRatingTagBrandCategory);
        $this->assertTrue($productsByRatingTagBrandCategory->contains($this->product));
    }

    public function test_product_can_get_products_by_price_range_rating_tag_brand_and_category()
    {
        $productsByPriceRatingTagBrandCategory = Product::getProductsByPriceRangeRatingTagBrandAndCategory(
            50,
            150,
            4.0,
            'teste',
            $this->brand->id,
            $this->category->id,
            2
        );

        $this->assertCount(1, $productsByPriceRatingTagBrandCategory);
        $this->assertTrue($productsByPriceRatingTagBrandCategory->contains($this->product));
    }
}