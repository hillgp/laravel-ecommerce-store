<?php

namespace Tests\Feature\Http\Controllers\Api;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Review;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Carbon\Carbon;

class ProductApiControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private User $adminUser;
    private User $regularUser;
    private Category $category;
    private Brand $brand;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        // Criar usuários
        $this->adminUser = User::factory()->create([
            'email' => 'admin@test.com',
            'is_admin' => true
        ]);

        $this->regularUser = User::factory()->create([
            'email' => 'user@test.com',
            'is_admin' => false
        ]);

        // Criar categoria e marca
        $this->category = Category::factory()->create([
            'name' => 'Eletrônicos',
            'slug' => 'eletronicos',
            'is_active' => true
        ]);

        $this->brand = Brand::factory()->create([
            'name' => 'Samsung',
            'slug' => 'samsung',
            'is_active' => true
        ]);

        // Criar produto
        $this->product = Product::factory()->create([
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'name' => 'Smartphone Samsung Galaxy',
            'slug' => 'smartphone-samsung-galaxy',
            'sku' => 'SAMSUNG-001',
            'price' => 1999.90,
            'compare_price' => 2299.90,
            'stock_quantity' => 50,
            'is_active' => true,
            'is_featured' => true,
            'tags' => ['smartphone', 'samsung', 'galaxy']
        ]);

        // Criar algumas avaliações
        Review::factory()->count(3)->create([
            'product_id' => $this->product->id,
            'rating' => 5
        ]);
    }

    public function test_can_list_all_products()
    {
        $response = $this->getJson('/api/v1/products');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        '*' => [
                            'id',
                            'name',
                            'slug',
                            'sku',
                            'price',
                            'compare_price',
                            'stock_quantity',
                            'is_active',
                            'is_featured',
                            'category' => [
                                'id',
                                'name',
                                'slug'
                            ],
                            'brand' => [
                                'id',
                                'name',
                                'slug'
                            ]
                        ]
                    ],
                    'pagination'
                ]);

        $this->assertTrue($response->json('success'));
        $this->assertCount(1, $response->json('data'));
    }

    public function test_can_list_products_with_pagination()
    {
        // Criar mais produtos para testar paginação
        Product::factory()->count(15)->create(['is_active' => true]);

        $response = $this->getJson('/api/v1/products?per_page=5&page=1');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data',
                    'pagination' => [
                        'current_page',
                        'per_page',
                        'total',
                        'last_page',
                        'from',
                        'to'
                    ]
                ]);

        $this->assertTrue($response->json('success'));
        $this->assertCount(5, $response->json('data'));
        $this->assertEquals(1, $response->json('pagination.current_page'));
        $this->assertEquals(5, $response->json('pagination.per_page'));
        $this->assertEquals(16, $response->json('pagination.total'));
    }

    public function test_can_filter_products_by_category()
    {
        $anotherCategory = Category::factory()->create(['is_active' => true]);
        Product::factory()->count(3)->create([
            'category_id' => $anotherCategory->id,
            'is_active' => true
        ]);

        $response = $this->getJson("/api/v1/products?category_id={$this->category->id}");

        $response->assertStatus(200)
                ->assertJsonStructure(['success', 'data', 'pagination']);

        $this->assertTrue($response->json('success'));
        $this->assertCount(1, $response->json('data'));

        $product = $response->json('data.0');
        $this->assertEquals($this->category->id, $product['category']['id']);
    }

    public function test_can_filter_products_by_brand()
    {
        $anotherBrand = Brand::factory()->create(['is_active' => true]);
        Product::factory()->count(2)->create([
            'brand_id' => $anotherBrand->id,
            'is_active' => true
        ]);

        $response = $this->getJson("/api/v1/products?brand_id={$this->brand->id}");

        $response->assertStatus(200)
                ->assertJsonStructure(['success', 'data', 'pagination']);

        $this->assertTrue($response->json('success'));
        $this->assertCount(1, $response->json('data'));

        $product = $response->json('data.0');
        $this->assertEquals($this->brand->id, $product['brand']['id']);
    }

    public function test_can_filter_products_by_price_range()
    {
        Product::factory()->create([
            'price' => 500.00,
            'is_active' => true
        ]);

        Product::factory()->create([
            'price' => 3000.00,
            'is_active' => true
        ]);

        $response = $this->getJson('/api/v1/products?min_price=1000&max_price=2500');

        $response->assertStatus(200)
                ->assertJsonStructure(['success', 'data', 'pagination']);

        $this->assertTrue($response->json('success'));
        $this->assertCount(1, $response->json('data'));

        $product = $response->json('data.0');
        $this->assertEquals($this->product->id, $product['id']);
    }

    public function test_can_filter_products_by_rating()
    {
        $lowRatedProduct = Product::factory()->create(['is_active' => true]);
        Review::factory()->create([
            'product_id' => $lowRatedProduct->id,
            'rating' => 2
        ]);

        $response = $this->getJson('/api/v1/products?min_rating=4.5');

        $response->assertStatus(200)
                ->assertJsonStructure(['success', 'data', 'pagination']);

        $this->assertTrue($response->json('success'));
        $this->assertCount(1, $response->json('data'));

        $product = $response->json('data.0');
        $this->assertEquals($this->product->id, $product['id']);
    }

    public function test_can_filter_products_by_stock_status()
    {
        $outOfStockProduct = Product::factory()->create([
            'stock_quantity' => 0,
            'is_active' => true
        ]);

        $response = $this->getJson('/api/v1/products?in_stock=1');

        $response->assertStatus(200)
                ->assertJsonStructure(['success', 'data', 'pagination']);

        $this->assertTrue($response->json('success'));
        $this->assertCount(1, $response->json('data'));

        $product = $response->json('data.0');
        $this->assertEquals($this->product->id, $product['id']);
    }

    public function test_can_filter_products_by_featured()
    {
        $regularProduct = Product::factory()->create([
            'is_featured' => false,
            'is_active' => true
        ]);

        $response = $this->getJson('/api/v1/products?featured=1');

        $response->assertStatus(200)
                ->assertJsonStructure(['success', 'data', 'pagination']);

        $this->assertTrue($response->json('success'));
        $this->assertCount(1, $response->json('data'));

        $product = $response->json('data.0');
        $this->assertEquals($this->product->id, $product['id']);
    }

    public function test_can_filter_products_by_discount()
    {
        $noDiscountProduct = Product::factory()->create([
            'price' => 1000.00,
            'compare_price' => null,
            'is_active' => true
        ]);

        $response = $this->getJson('/api/v1/products?on_sale=1');

        $response->assertStatus(200)
                ->assertJsonStructure(['success', 'data', 'pagination']);

        $this->assertTrue($response->json('success'));
        $this->assertCount(1, $response->json('data'));

        $product = $response->json('data.0');
        $this->assertEquals($this->product->id, $product['id']);
    }

    public function test_can_search_products()
    {
        $response = $this->getJson('/api/v1/products?search=Galaxy');

        $response->assertStatus(200)
                ->assertJsonStructure(['success', 'data', 'pagination']);

        $this->assertTrue($response->json('success'));
        $this->assertCount(1, $response->json('data'));

        $product = $response->json('data.0');
        $this->assertEquals($this->product->id, $product['id']);
    }

    public function test_can_sort_products_by_name()
    {
        $productB = Product::factory()->create([
            'name' => 'Apple iPhone',
            'is_active' => true
        ]);

        $response = $this->getJson('/api/v1/products?sort=name&order=asc');

        $response->assertStatus(200)
                ->assertJsonStructure(['success', 'data', 'pagination']);

        $this->assertTrue($response->json('success'));
        $this->assertCount(2, $response->json('data'));

        $products = $response->json('data');
        $this->assertEquals($productB->id, $products[0]['id']);
        $this->assertEquals($this->product->id, $products[1]['id']);
    }

    public function test_can_sort_products_by_price()
    {
        $cheapProduct = Product::factory()->create([
            'price' => 500.00,
            'is_active' => true
        ]);

        $response = $this->getJson('/api/v1/products?sort=price&order=asc');

        $response->assertStatus(200)
                ->assertJsonStructure(['success', 'data', 'pagination']);

        $this->assertTrue($response->json('success'));
        $this->assertCount(2, $response->json('data'));

        $products = $response->json('data');
        $this->assertEquals($cheapProduct->id, $products[0]['id']);
        $this->assertEquals($this->product->id, $products[1]['id']);
    }

    public function test_can_sort_products_by_rating()
    {
        $lowRatedProduct = Product::factory()->create(['is_active' => true]);
        Review::factory()->create([
            'product_id' => $lowRatedProduct->id,
            'rating' => 3
        ]);

        $response = $this->getJson('/api/v1/products?sort=rating&order=desc');

        $response->assertStatus(200)
                ->assertJsonStructure(['success', 'data', 'pagination']);

        $this->assertTrue($response->json('success'));
        $this->assertCount(2, $response->json('data'));

        $products = $response->json('data');
        $this->assertEquals($this->product->id, $products[0]['id']);
        $this->assertEquals($lowRatedProduct->id, $products[1]['id']);
    }

    public function test_can_sort_products_by_created_date()
    {
        $olderProduct = Product::factory()->create([
            'created_at' => Carbon::now()->subDays(5),
            'is_active' => true
        ]);

        $response = $this->getJson('/api/v1/products?sort=created_at&order=desc');

        $response->assertStatus(200)
                ->assertJsonStructure(['success', 'data', 'pagination']);

        $this->assertTrue($response->json('success'));
        $this->assertCount(2, $response->json('data'));

        $products = $response->json('data');
        $this->assertEquals($this->product->id, $products[0]['id']);
        $this->assertEquals($olderProduct->id, $products[1]['id']);
    }

    public function test_can_get_single_product()
    {
        $response = $this->getJson("/api/v1/products/{$this->product->id}");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'id',
                        'name',
                        'slug',
                        'sku',
                        'description',
                        'price',
                        'compare_price',
                        'stock_quantity',
                        'is_active',
                        'is_featured',
                        'category' => [
                            'id',
                            'name',
                            'slug'
                        ],
                        'brand' => [
                            'id',
                            'name',
                            'slug'
                        ],
                        'images',
                        'reviews' => [
                            '*' => [
                                'id',
                                'rating',
                                'comment',
                                'customer_name',
                                'created_at'
                            ]
                        ],
                        'average_rating',
                        'total_reviews'
                    ]
                ]);

        $this->assertTrue($response->json('success'));
        $this->assertEquals($this->product->id, $response->json('data.id'));
        $this->assertEquals($this->product->name, $response->json('data.name'));
    }

    public function test_can_get_featured_products()
    {
        $regularProduct = Product::factory()->create([
            'is_featured' => false,
            'is_active' => true
        ]);

        $response = $this->getJson('/api/v1/products/featured');

        $response->assertStatus(200)
                ->assertJsonStructure(['success', 'data', 'pagination']);

        $this->assertTrue($response->json('success'));
        $this->assertCount(1, $response->json('data'));

        $product = $response->json('data.0');
        $this->assertEquals($this->product->id, $product['id']);
    }

    public function test_can_get_products_by_category()
    {
        $response = $this->getJson("/api/v1/products/category/{$this->category->slug}");

        $response->assertStatus(200)
                ->assertJsonStructure(['success', 'data', 'pagination']);

        $this->assertTrue($response->json('success'));
        $this->assertCount(1, $response->json('data'));

        $product = $response->json('data.0');
        $this->assertEquals($this->product->id, $product['id']);
    }

    public function test_can_get_products_by_brand()
    {
        $response = $this->getJson("/api/v1/products/brand/{$this->brand->slug}");

        $response->assertStatus(200)
                ->assertJsonStructure(['success', 'data', 'pagination']);

        $this->assertTrue($response->json('success'));
        $this->assertCount(1, $response->json('data'));

        $product = $response->json('data.0');
        $this->assertEquals($this->product->id, $product['id']);
    }

    public function test_can_get_product_reviews()
    {
        $response = $this->getJson("/api/v1/products/{$this->product->id}/reviews");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        '*' => [
                            'id',
                            'rating',
                            'comment',
                            'customer_name',
                            'created_at'
                        ]
                    ],
                    'pagination'
                ]);

        $this->assertTrue($response->json('success'));
        $this->assertCount(3, $response->json('data'));
    }

    public function test_admin_can_create_product()
    {
        Sanctum::actingAs($this->adminUser);

        $productData = [
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'name' => 'Novo Smartphone',
            'slug' => 'novo-smartphone',
            'sku' => 'NEWSMART-001',
            'description' => 'Descrição do novo smartphone',
            'price' => 2999.90,
            'compare_price' => 3299.90,
            'cost' => 2000.00,
            'stock_quantity' => 25,
            'min_stock' => 5,
            'max_stock' => 100,
            'is_active' => true,
            'is_featured' => false,
            'weight' => 0.5,
            'length' => 15,
            'width' => 7,
            'height' => 1,
            'meta_title' => 'Novo Smartphone - Loja Online',
            'meta_description' => 'Compre o novo smartphone na nossa loja',
            'tags' => ['smartphone', 'novo', 'tecnologia']
        ];

        $response = $this->postJson('/api/v1/admin/products', $productData);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'id',
                        'name',
                        'slug',
                        'sku',
                        'price',
                        'category' => [
                            'id',
                            'name'
                        ],
                        'brand' => [
                            'id',
                            'name'
                        ]
                    ],
                    'message'
                ]);

        $this->assertTrue($response->json('success'));
        $this->assertEquals('Produto criado com sucesso', $response->json('message'));
        $this->assertEquals('Novo Smartphone', $response->json('data.name'));
    }

    public function test_regular_user_cannot_create_product()
    {
        Sanctum::actingAs($this->regularUser);

        $productData = [
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'name' => 'Produto Teste',
            'slug' => 'produto-teste',
            'sku' => 'TEST-002',
            'price' => 100.00,
            'stock_quantity' => 10,
            'is_active' => true
        ];

        $response = $this->postJson('/api/v1/admin/products', $productData);

        $response->assertStatus(403)
                ->assertJsonStructure(['success', 'message']);

        $this->assertFalse($response->json('success'));
        $this->assertEquals('Acesso negado', $response->json('message'));
    }

    public function test_admin_can_update_product()
    {
        Sanctum::actingAs($this->adminUser);

        $updateData = [
            'name' => 'Smartphone Samsung Galaxy Atualizado',
            'price' => 2199.90,
            'stock_quantity' => 75,
            'is_featured' => true
        ];

        $response = $this->putJson("/api/v1/admin/products/{$this->product->id}", $updateData);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'id',
                        'name',
                        'slug',
                        'price',
                        'stock_quantity',
                        'is_featured'
                    ],
                    'message'
                ]);

        $this->assertTrue($response->json('success'));
        $this->assertEquals('Produto atualizado com sucesso', $response->json('message'));
        $this->assertEquals('Smartphone Samsung Galaxy Atualizado', $response->json('data.name'));
        $this->assertEquals(2199.90, $response->json('data.price'));
        $this->assertEquals(75, $response->json('data.stock_quantity'));
        $this->assertTrue($response->json('data.is_featured'));
    }

    public function test_admin_can_delete_product()
    {
        Sanctum::actingAs($this->adminUser);

        $response = $this->deleteJson("/api/v1/admin/products/{$this->product->id}");

        $response->assertStatus(200)
                ->assertJsonStructure(['success', 'message']);

        $this->assertTrue($response->json('success'));
        $this->assertEquals('Produto excluído com sucesso', $response->json('message'));

        $this->assertSoftDeleted('products', ['id' => $this->product->id]);
    }

    public function test_can_get_product_search_suggestions()
    {
        $response = $this->getJson('/api/v1/products/search?suggestions=1&q=Sam');

        $response->assertStatus(200)
                ->assertJsonStructure(['success', 'data']);

        $this->assertTrue($response->json('success'));
        $this->assertIsArray($response->json('data'));
    }

    public function test_can_get_product_filters()
    {
        $response = $this->getJson('/api/v1/products/filters');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'categories' => [
                            '*' => [
                                'id',
                                'name',
                                'slug',
                                'products_count'
                            ]
                        ],
                        'brands' => [
                            '*' => [
                                'id',
                                'name',
                                'slug',
                                'products_count'
                            ]
                        ],
                        'price_range' => [
                            'min',
                            'max'
                        ]
                    ]
                ]);

        $this->assertTrue($response->json('success'));
    }

    public function test_can_get_product_stats()
    {
        $response = $this->getJson('/api/v1/products/stats');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'total_products',
                        'active_products',
                        'featured_products',
                        'out_of_stock_products',
                        'low_stock_products',
                        'average_price',
                        'average_rating'
                    ]
                ]);

        $this->assertTrue($response->json('success'));
    }

    public function test_can_get_similar_products()
    {
        $similarProduct = Product::factory()->create([
            'brand_id' => $this->brand->id,
            'is_active' => true
        ]);

        $response = $this->getJson("/api/v1/products/{$this->product->id}/similar");

        $response->assertStatus(200)
                ->assertJsonStructure(['success', 'data', 'pagination']);

        $this->assertTrue($response->json('success'));
    }

    public function test_can_get_related_products()
    {
        $relatedProduct = Product::factory()->create([
            'category_id' => $this->category->id,
            'is_active' => true
        ]);

        $response = $this->getJson("/api/v1/products/{$this->product->id}/related");

        $response->assertStatus(200)
                ->assertJsonStructure(['success', 'data', 'pagination']);

        $this->assertTrue($response->json('success'));
    }

    public function test_can_get_recent_products()
    {
        $response = $this->getJson('/api/v1/products/recent?limit=5');

        $response->assertStatus(200)
                ->assertJsonStructure(['success', 'data', 'pagination']);

        $this->assertTrue($response->json('success'));
    }

    public function test_can_get_discounted_products()
    {
        $response = $this->getJson('/api/v1/products/discounted?limit=5');

        $response->assertStatus(200)
                ->assertJsonStructure(['success', 'data', 'pagination']);

        $this->assertTrue($response->json('success'));
        $this->assertCount(1, $response->json('data'));

        $product = $response->json('data.0');
        $this->assertEquals($this->product->id, $product['id']);
    }

    public function test_can_get_best_sellers()
    {
        $response = $this->getJson('/api/v1/products/best-sellers?limit=5');

        $response->assertStatus(200)
                ->assertJsonStructure(['success', 'data', 'pagination']);

        $this->assertTrue($response->json('success'));
    }

    public function test_can_get_new_arrivals()
    {
        $response = $this->getJson('/api/v1/products/new-arrivals?limit=5');

        $response->assertStatus(200)
                ->assertJsonStructure(['success', 'data', 'pagination']);

        $this->assertTrue($response->json('success'));
    }

    public function test_can_get_popular_products()
    {
        $response = $this->getJson('/api/v1/products/popular?limit=5');

        $response->assertStatus(200)
                ->assertJsonStructure(['success', 'data', 'pagination']);

        $this->assertTrue($response->json('success'));
    }

    public function test_can_get_products_by_tag()
    {
        $response = $this->getJson('/api/v1/products/tag/smartphone');

        $response->assertStatus(200)
                ->assertJsonStructure(['success', 'data', 'pagination']);

        $this->assertTrue($response->json('success'));
        $this->assertCount(1, $response->json('data'));

        $product = $response->json('data.0');
        $this->assertEquals($this->product->id, $product['id']);
    }

    public function test_can_get_products_by_multiple_tags()
    {
        $this->product->update(['tags' => ['smartphone', 'samsung', 'galaxy']]);

        $response = $this->getJson('/api/v1/products/tags?tags[]=smartphone&tags[]=samsung');

        $response->assertStatus(200)
                ->assertJsonStructure(['success', 'data', 'pagination']);

        $this->assertTrue($response->json('success'));
        $this->assertCount(1, $response->json('data'));
    }

    public function test_can_get_products_by_price_range_and_category()
    {
        $response = $this->getJson("/api/v1/products/price-range?min_price=1500&max_price=2500&category_id={$this->category->id}");

        $response->assertStatus(200)
                ->assertJsonStructure(['success', 'data', 'pagination']);

        $this->assertTrue($response->json('success'));
        $this->assertCount(1, $response->json('data'));
    }

    public function test_can_get_products_by_rating_and_category()
    {
        $response = $this->getJson("/api/v1/products/rating-range?min_rating=4.5&category_id={$this->category->id}");

        $response->assertStatus(200)
                ->assertJsonStructure(['success', 'data', 'pagination']);

        $this->assertTrue($response->json('success'));
        $this->assertCount(1, $response->json('data'));
    }

    public function test_can_get_products_by_brand_and_category()
    {
        $response = $this->getJson("/api/v1/products/brand-category?brand_id={$this->brand->id}&category_id={$this->category->id}");

        $response->assertStatus(200)
                ->assertJsonStructure(['success', 'data', 'pagination']);

        $this->assertTrue($response->json('success'));
        $this->assertCount(1, $response->json('data'));
    }

    public function test_can_get_products_by_tag_and_category()
    {
        $response = $this->getJson("/api/v1/products/tag-category?tag=smartphone&category_id={$this->category->id}");

        $response->assertStatus(200)
                ->assertJsonStructure(['success', 'data', 'pagination']);

        $this->assertTrue($response->json('success'));
        $this->assertCount(1, $response->json('data'));
    }

    public function test_can_get_products_by_price_range_and_brand()
    {
        $response = $this->getJson("/api/v1/products/price-brand?min_price=1500&max_price=2500&brand_id={$this->brand->id}");

        $response->assertStatus(200)
                ->assertJsonStructure(['success', 'data', 'pagination']);

        $this->assertTrue($response->json('success'));
        $this->assertCount(1, $response->json('data'));
    }

    public function test_can_get_products_by_rating_and_brand()
    {
        $response = $this->getJson("/api/v1/products/rating-brand?min_rating=4.5&brand_id={$this->brand->id}");

        $response->assertStatus(200)
                ->assertJsonStructure(['success', 'data', 'pagination']);

        $this->assertTrue($response->json('success'));
        $this->assertCount(1, $response->json('data'));
    }

    public function test_can_get_products_by_tag_and_brand()
    {
        $response = $this->getJson("/api/v1/products/tag-brand?tag=smartphone&brand_id={$this->brand->id}");

        $response->assertStatus(200)
                ->assertJsonStructure(['success', 'data', 'pagination']);

        $this->assertTrue($response->json('success'));
        $this->assertCount(1, $response->json('data'));
    }

    public function test_can_get_products_by_price_range_and_tag()
    {
        $response = $this->getJson('/api/v1/products/price-tag?min_price=1500&max_price=2500&tag=smartphone');

        $response->assertStatus(200)
                ->assertJsonStructure(['success', 'data', 'pagination']);

        $this->assertTrue($response->json('success'));
        $this->assertCount(1, $response->json('data'));
    }

    public function test_can_get_products_by_rating_and_tag()
    {
        $response = $this->getJson('/api/v1/products/rating-tag?min_rating=4.5&tag=smartphone');

        $response->assertStatus(200)
                ->assertJsonStructure(['success', 'data', 'pagination']);

        $this->assertTrue($response->json('success'));
        $this->assertCount(1, $response->json('data'));
    }

    public function test_can_get_products_by_price_range_rating_and_category()
    {
        $response = $this->getJson("/api/v1/products/price-rating-category?min_price=1500&max_price=2500&min_rating=4.5&category_id={$this->category->id}");

        $response->assertStatus(200)
                ->assertJsonStructure(['success', 'data', 'pagination']);

        $this->assertTrue($response->json('success'));
        $this->assertCount(1, $response->json('data'));
    }

    public function test_can_get_products_by_price_range_rating_and_brand()
    {
        $response = $this->getJson("/api/v1/products/price-rating-brand?min_price=1500&max_price=2500&min_rating=4.5&brand_id={$this->brand->id}");

        $response->assertStatus(200)
                ->assertJsonStructure(['success', 'data', 'pagination']);

        $this->assertTrue($response->json('success'));
        $this->assertCount(1, $response->json('data'));
    }

    public function test_can_get_products_by_price_range_rating_and_tag()
    {
        $response = $this->getJson('/api/v1/products/price-rating-tag?min_price=1500&max_price=2500&min_rating=4.5&tag=smartphone');

        $response->assertStatus(200)
                ->assertJsonStructure(['success', 'data', 'pagination']);

        $this->assertTrue($response->json('success'));
        $this->assertCount(1, $response->json('data'));
    }

    public function test_can_get_products_by_price_range_brand_and_category()
    {
        $response = $this->getJson("/api/v1/products/price-brand-category?min_price=1500&max_price=2500&brand_id={$this->brand->id}&category_id={$this->category->id}");

        $response->assertStatus(200)
                ->assertJsonStructure(['success', 'data', 'pagination']);

        $this->assertTrue($response->json('success'));
        $this->assertCount(1, $response->json('data'));
    }

    public function test_can_get_products_by_price_range_tag_and_category()
    {
        $response = $this->getJson("/api/v1/products/price-tag-category?min_price=1500&max_price=2500&tag=smartphone&category_id={$this->category->id}");

        $response->assertStatus(200)
                ->assertJsonStructure(['success', 'data', 'pagination']);

        $this->assertTrue($response->json('success'));
        $this->assertCount(1, $response->json('data'));
    }

    public function test_can_get_products_by_price_range_tag_and_brand()
    {
        $response = $this->getJson("/api/v1/products/price-tag-brand?min_price=1500&max_price=2500&tag=smartphone&brand_id={$this->brand->id}");

        $response->assertStatus(200)
                ->assertJsonStructure(['success', 'data', 'pagination']);

        $this->assertTrue($response->json('success'));
        $this->assertCount(1, $response->json('data'));
    }

    public function test_can_get_products_by_rating_brand_and_category()
    {
        $response = $this->getJson("/api/v1/products/rating-brand-category?min_rating=4.5&brand_id={$this->brand->id}&category_id={$this->category->id}");

        $response->assertStatus(200)
                ->assertJsonStructure(['success', 'data', 'pagination']);

        $this->assertTrue($response->json('success'));
        $this->assertCount(1, $response->json('data'));
    }

    public function test_can_get_products_by_rating_tag_and_category()
    {
        $response = $this->getJson("/api/v1/products/rating-tag-category?min_rating=4.5&tag=smartphone&category_id={$this->category->id}");

        $response->assertStatus(200)
                ->assertJsonStructure(['success', 'data', 'pagination']);

        $this->assertTrue($response->json('success'));
        $this->assertCount(1, $response->json('data'));
    }

    public function test_can_get_products_by_rating_tag_and_brand()
    {
        $response = $this->getJson("/api/v1/products/rating-tag-brand?min_rating=4.5&tag=smartphone&brand_id={$this->brand->id}");

        $response->assertStatus(200)
                ->assertJsonStructure(['success', 'data', 'pagination']);

        $this->assertTrue($response->json('success'));
        $this->assertCount(1, $response->json('data'));
    }

    public function test_can_get_products_by_tag_brand_and_category()
    {
        $response = $this->getJson("/api/v1/products/tag-brand-category?tag=smartphone&brand_id={$this->brand->id}&category_id={$this->category->id}");

        $response->assertStatus(200)
                ->assertJsonStructure(['success', 'data', 'pagination']);

        $this->assertTrue($response->json('success'));
        $this->assertCount(1, $response->json('data'));
    }

    public function test_can_get_products_by_price_range_rating_brand_and_category()
    {
        $response = $this->getJson("/api/v1/products/price-rating-brand-category?min_price=1500&max_price=2500&min_rating=4.5&brand_id={$this->brand->id}&category_id={$this->category->id}");

        $response->assertStatus(200)
                ->assertJsonStructure(['success', 'data', 'pagination']);

        $this->assertTrue($response->json('success'));
        $this->assertCount(1, $response->json('data'));
    }

    public function test_can_get_products_by_price_range_rating_tag_and_category()
    {
        $response = $this->getJson("/api/v1/products/price-rating-tag-category?min_price=1500&max_price=2500&min_rating=4.5&tag=smartphone&category_id={$this->category->id}");

        $response->assertStatus(200)
                ->assertJsonStructure(['success', 'data', 'pagination']);

        $this->assertTrue($response->json('success'));
        $this->assertCount(1, $response->json('data'));
    }

    public function test_can_get_products_by_price_range_rating_tag_and_brand()
    {
        $response = $this->getJson("/api/v1/products/price-rating-tag-brand?min_price=1500&max_price=2500&min_rating=4.5&tag=smartphone&brand_id={$this->brand->id}");

        $response->assertStatus(200)
                ->assertJsonStructure(['success', 'data', 'pagination']);

        $this->assertTrue($response->json('success'));
        $this->assertCount(1, $response->json('data'));
    }

    public function test_can_get_products_by_price_range_tag_brand_and_category()
    {
        $response = $this->getJson("/api/v1/products/price-tag-brand-category?min_price=1500&max_price=2500&tag=smartphone&brand_id={$this->brand->id}&category_id={$this->category->id}");

        $response->assertStatus(200)
                ->assertJsonStructure(['success', 'data', 'pagination']);

        $this->assertTrue($response->json('success'));
        $this->assertCount(1, $response->json('data'));
    }

    public function test_can_get_products_by_rating_tag_brand_and_category()
    {
        $response = $this->getJson("/api/v1/products/rating-tag-brand-category?min_rating=4.5&tag=smartphone&brand_id={$this->brand->id}&category_id={$this->category->id}");

        $response->assertStatus(200)
                ->assertJsonStructure(['success', 'data', 'pagination']);

        $this->assertTrue($response->json('success'));
        $this->assertCount(1, $response->json('data'));
    }

    public function test_can_get_products_by_price_range_rating_tag_brand_and_category()
    {
        $response = $this->getJson("/api/v1/products/price-rating-tag-brand-category?min_price=1500&max_price=2500&min_rating=4.5&tag=smartphone&brand_id={$this->brand->id}&category_id={$this->category->id}");

        $response->assertStatus(200)
                ->assertJsonStructure(['success', 'data', 'pagination']);

        $this->assertTrue($response->json('success'));
        $this->assertCount(1, $response->json('data'));
    }

    public function test_returns_404_for_nonexistent_product()
    {
        $response = $this->getJson('/api/v1/products/99999');

        $response->assertStatus(404)
                ->assertJsonStructure(['success', 'message']);

        $this->assertFalse($response->json('success'));
        $this->assertEquals('Produto não encontrado', $response->json('message'));
    }

    public function test_returns_404_for_nonexistent_category()
    {
        $response = $this->getJson('/api/v1/products/category/nonexistent-category');

        $response->assertStatus(404)
                ->assertJsonStructure(['success', 'message']);

        $this->assertFalse($response->json('success'));
        $this->assertEquals('Categoria não encontrada', $response->json('message'));
    }

    public function test_returns_404_for_nonexistent_brand()
    {
        $response = $this->getJson('/api/v1/products/brand/nonexistent-brand');

        $response->assertStatus(404)
                ->assertJsonStructure(['success', 'message']);

        $this->assertFalse($response->json('success'));
        $this->assertEquals('Marca não encontrada', $response->json('message'));
    }

    public function test_validation_fails_for_invalid_product_data()
    {
        Sanctum::actingAs($this->adminUser);

        $invalidData = [
            'name' => '', // Nome obrigatório
            'price' => -100, // Preço negativo
            'stock_quantity' => -5, // Estoque negativo
        ];

        $response = $this->postJson('/api/v1/admin/products', $invalidData);

        $response->assertStatus(422)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'errors' => [
                        'name',
                        'price',
                        'stock_quantity'
                    ]
                ]);

        $this->assertFalse($response->json('success'));
        $this->assertEquals('Dados inválidos', $response->json('message'));
    }

    public function test_validation_fails_for_invalid_update_data()
    {
        Sanctum::actingAs($this->adminUser);

        $invalidData = [
            'price' => -50, // Preço negativo
            'stock_quantity' => -10, // Estoque negativo
        ];

        $response = $this->putJson("/api/v1/admin/products/{$this->product->id}", $invalidData);

        $response->assertStatus(422)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'errors' => [
                        'price',
                        'stock_quantity'
                    ]
                ]);

        $this->assertFalse($response->json('success'));
        $this->assertEquals('Dados inválidos', $response->json('message'));
    }

    public function test_can_get_product_with_cache_headers()
    {
        $response = $this->getJson('/api/v1/products');

        $response->assertStatus(200)
                ->assertHeader('X-Cache-Hit', 'false')
                ->assertHeader('Cache-Control', 'max-age=300');
    }

    public function test_can_get_product_with_etag()
    {
        $response = $this->getJson('/api/v1/products');

        $response->assertStatus(200)
                ->assertHeader('ETag');

        $etag = $response->getHeader('ETag')[0];

        // Segunda requisição com ETag
        $response2 = $this->getJson('/api/v1/products', [
            'If-None-Match' => $etag
        ]);

        $response2->assertStatus(304);
    }
}