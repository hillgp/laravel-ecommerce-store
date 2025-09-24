<?php

namespace LaravelEcommerce\Store\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class CacheService
{
    /**
     * Cache keys padrão.
     */
    protected array $keys = [
        'products' => 'store:products',
        'products_featured' => 'store:products:featured',
        'products_category' => 'store:products:category:%s',
        'products_brand' => 'store:products:brand:%s',
        'categories' => 'store:categories',
        'categories_active' => 'store:categories:active',
        'brands' => 'store:brands',
        'brands_active' => 'store:brands:active',
        'product_details' => 'store:product:%s',
        'product_reviews' => 'store:product:%s:reviews',
        'product_images' => 'store:product:%s:images',
        'customer_details' => 'store:customer:%s',
        'customer_orders' => 'store:customer:%s:orders',
        'order_details' => 'store:order:%s',
        'cart_items' => 'store:cart:%s',
        'settings' => 'store:settings',
        'currencies' => 'store:currencies',
        'payment_methods' => 'store:payment:methods',
        'shipping_methods' => 'store:shipping:methods',
        'dashboard_stats' => 'store:dashboard:stats',
        'reports_sales' => 'store:reports:sales:%s',
        'reports_products' => 'store:reports:products:%s',
        'search_results' => 'store:search:%s',
        'popular_searches' => 'store:popular:searches',
    ];

    /**
     * Cache de produtos.
     */
    public function getProducts(array $filters = [], int $ttl = 3600): Collection
    {
        $key = $this->buildKey('products', $filters);

        return Cache::remember($key, $ttl, function () use ($filters) {
            $query = \LaravelEcommerce\Store\Models\Product::with(['category', 'brand', 'images']);

            if (isset($filters['category'])) {
                $query->where('category_id', $filters['category']);
            }

            if (isset($filters['brand'])) {
                $query->where('brand_id', $filters['brand']);
            }

            if (isset($filters['featured'])) {
                $query->where('is_featured', $filters['featured']);
            }

            if (isset($filters['active'])) {
                $query->where('is_active', $filters['active']);
            }

            if (isset($filters['limit'])) {
                $query->limit($filters['limit']);
            }

            return $query->orderBy('created_at', 'desc')->get();
        });
    }

    /**
     * Cache de produtos em destaque.
     */
    public function getFeaturedProducts(int $limit = 12, int $ttl = 1800): Collection
    {
        $key = $this->keys['products_featured'] . ":{$limit}";

        return Cache::remember($key, $ttl, function () use ($limit) {
            return \LaravelEcommerce\Store\Models\Product::with(['category', 'brand', 'images'])
                ->where('is_featured', true)
                ->where('is_active', true)
                ->where('stock_quantity', '>', 0)
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();
        });
    }

    /**
     * Cache de produtos por categoria.
     */
    public function getProductsByCategory(int $categoryId, int $limit = 20, int $ttl = 3600): Collection
    {
        $key = sprintf($this->keys['products_category'], $categoryId) . ":{$limit}";

        return Cache::remember($key, $ttl, function () use ($categoryId, $limit) {
            return \LaravelEcommerce\Store\Models\Product::with(['category', 'brand', 'images'])
                ->where('category_id', $categoryId)
                ->where('is_active', true)
                ->where('stock_quantity', '>', 0)
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();
        });
    }

    /**
     * Cache de produtos por marca.
     */
    public function getProductsByBrand(int $brandId, int $limit = 20, int $ttl = 3600): Collection
    {
        $key = sprintf($this->keys['products_brand'], $brandId) . ":{$limit}";

        return Cache::remember($key, $ttl, function () use ($brandId, $limit) {
            return \LaravelEcommerce\Store\Models\Product::with(['category', 'brand', 'images'])
                ->where('brand_id', $brandId)
                ->where('is_active', true)
                ->where('stock_quantity', '>', 0)
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();
        });
    }

    /**
     * Cache de detalhes do produto.
     */
    public function getProductDetails(int $productId, int $ttl = 3600)
    {
        $key = sprintf($this->keys['product_details'], $productId);

        return Cache::remember($key, $ttl, function () use ($productId) {
            return \LaravelEcommerce\Store\Models\Product::with([
                'category',
                'brand',
                'images',
                'reviews' => function ($query) {
                    $query->with('customer')->latest();
                },
                'variations',
                'relatedProducts' => function ($query) {
                    $query->limit(8);
                }
            ])->find($productId);
        });
    }

    /**
     * Cache de avaliações do produto.
     */
    public function getProductReviews(int $productId, int $ttl = 1800): Collection
    {
        $key = sprintf($this->keys['product_reviews'], $productId);

        return Cache::remember($key, $ttl, function () use ($productId) {
            return \LaravelEcommerce\Store\Models\Review::with('customer')
                ->where('product_id', $productId)
                ->where('is_approved', true)
                ->orderBy('created_at', 'desc')
                ->get();
        });
    }

    /**
     * Cache de categorias.
     */
    public function getCategories(bool $activeOnly = false, int $ttl = 3600): Collection
    {
        $key = $activeOnly ? $this->keys['categories_active'] : $this->keys['categories'];

        return Cache::remember($key, $ttl, function () use ($activeOnly) {
            $query = \LaravelEcommerce\Store\Models\Category::withCount('products');

            if ($activeOnly) {
                $query->where('is_active', true);
            }

            return $query->orderBy('name')->get();
        });
    }

    /**
     * Cache de marcas.
     */
    public function getBrands(bool $activeOnly = false, int $ttl = 3600): Collection
    {
        $key = $activeOnly ? $this->keys['brands_active'] : $this->keys['brands'];

        return Cache::remember($key, $ttl, function () use ($activeOnly) {
            $query = \LaravelEcommerce\Store\Models\Brand::withCount('products');

            if ($activeOnly) {
                $query->where('is_active', true);
            }

            return $query->orderBy('name')->get();
        });
    }

    /**
     * Cache de configurações.
     */
    public function getSettings(int $ttl = 3600)
    {
        return Cache::remember($this->keys['settings'], $ttl, function () {
            return [
                'store_name' => config('store.name', 'Loja Online'),
                'store_email' => config('store.email', 'contato@loja.com'),
                'store_phone' => config('store.phone', '(11) 9999-9999'),
                'currency' => config('store.currency', 'BRL'),
                'currency_symbol' => config('store.currency_symbol', 'R$'),
                'maintenance_mode' => config('store.maintenance_mode', false),
                'allow_guest_checkout' => config('store.allow_guest_checkout', true),
                'require_email_verification' => config('store.require_email_verification', false),
                'auto_approve_reviews' => config('store.auto_approve_reviews', true),
                'enable_notifications' => config('store.enable_notifications', true),
            ];
        });
    }

    /**
     * Cache de métodos de pagamento.
     */
    public function getPaymentMethods(int $ttl = 3600): Collection
    {
        return Cache::remember($this->keys['payment_methods'], $ttl, function () {
            return collect(config('store.payment_methods', [
                'credit_card' => ['name' => 'Cartão de Crédito', 'enabled' => true],
                'debit_card' => ['name' => 'Cartão de Débito', 'enabled' => true],
                'boleto' => ['name' => 'Boleto Bancário', 'enabled' => true],
                'pix' => ['name' => 'PIX', 'enabled' => true],
                'bank_transfer' => ['name' => 'Transferência Bancária', 'enabled' => false],
            ]))->filter(function ($method) {
                return $method['enabled'] ?? false;
            });
        });
    }

    /**
     * Cache de métodos de entrega.
     */
    public function getShippingMethods(int $ttl = 3600): Collection
    {
        return Cache::remember($this->keys['shipping_methods'], $ttl, function () {
            return collect(config('store.shipping_methods', [
                'standard' => [
                    'name' => 'Entrega Padrão',
                    'description' => '5-7 dias úteis',
                    'price' => 15.00,
                    'enabled' => true
                ],
                'express' => [
                    'name' => 'Entrega Expressa',
                    'description' => '1-2 dias úteis',
                    'price' => 25.00,
                    'enabled' => true
                ],
                'pickup' => [
                    'name' => 'Retirada na Loja',
                    'description' => 'Retire em nossa loja física',
                    'price' => 0.00,
                    'enabled' => false
                ],
            ]))->filter(function ($method) {
                return $method['enabled'] ?? false;
            });
        });
    }

    /**
     * Cache de estatísticas do dashboard.
     */
    public function getDashboardStats(int $ttl = 1800): array
    {
        return Cache::remember($this->keys['dashboard_stats'], $ttl, function () {
            return [
                'total_products' => \LaravelEcommerce\Store\Models\Product::count(),
                'active_products' => \LaravelEcommerce\Store\Models\Product::where('is_active', true)->count(),
                'featured_products' => \LaravelEcommerce\Store\Models\Product::where('is_featured', true)->count(),
                'low_stock_products' => \LaravelEcommerce\Store\Models\Product::where('stock_quantity', '<=', 5)->count(),
                'out_of_stock_products' => \LaravelEcommerce\Store\Models\Product::where('stock_quantity', '<=', 0)->count(),
                'total_categories' => \LaravelEcommerce\Store\Models\Category::count(),
                'active_categories' => \LaravelEcommerce\Store\Models\Category::where('is_active', true)->count(),
                'total_customers' => \LaravelEcommerce\Store\Models\Customer::count(),
                'active_customers' => \LaravelEcommerce\Store\Models\Customer::where('is_active', true)->count(),
                'verified_customers' => \LaravelEcommerce\Store\Models\Customer::whereNotNull('email_verified_at')->count(),
                'total_orders' => \LaravelEcommerce\Store\Models\Order::count(),
                'pending_orders' => \LaravelEcommerce\Store\Models\Order::where('status', 'pending')->count(),
                'confirmed_orders' => \LaravelEcommerce\Store\Models\Order::where('status', 'confirmed')->count(),
                'shipped_orders' => \LaravelEcommerce\Store\Models\Order::where('status', 'shipped')->count(),
                'delivered_orders' => \LaravelEcommerce\Store\Models\Order::where('status', 'delivered')->count(),
                'cancelled_orders' => \LaravelEcommerce\Store\Models\Order::where('status', 'cancelled')->count(),
                'today_orders' => \LaravelEcommerce\Store\Models\Order::whereDate('created_at', today())->count(),
                'today_revenue' => \LaravelEcommerce\Store\Models\Order::whereDate('created_at', today())->sum('total'),
                'month_orders' => \LaravelEcommerce\Store\Models\Order::whereMonth('created_at', now()->month)->count(),
                'month_revenue' => \LaravelEcommerce\Store\Models\Order::whereMonth('created_at', now()->month)->sum('total'),
                'total_reviews' => \LaravelEcommerce\Store\Models\Review::count(),
                'approved_reviews' => \LaravelEcommerce\Store\Models\Review::where('is_approved', true)->count(),
                'pending_reviews' => \LaravelEcommerce\Store\Models\Review::where('is_approved', false)->count(),
                'avg_rating' => \LaravelEcommerce\Store\Models\Review::avg('rating') ?? 0,
            ];
        });
    }

    /**
     * Cache de resultados de busca.
     */
    public function getSearchResults(string $query, int $limit = 20, int $ttl = 1800): Collection
    {
        $key = sprintf($this->keys['search_results'], md5($query)) . ":{$limit}";

        return Cache::remember($key, $ttl, function () use ($query, $limit) {
            return \LaravelEcommerce\Store\Models\Product::where('is_active', true)
                ->where('stock_quantity', '>', 0)
                ->where(function ($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%")
                      ->orWhere('description', 'like', "%{$query}%")
                      ->orWhere('sku', 'like', "%{$query}%");
                })
                ->with(['category', 'brand', 'images'])
                ->orderBy('name')
                ->limit($limit)
                ->get();
        });
    }

    /**
     * Cache de buscas populares.
     */
    public function getPopularSearches(int $limit = 10, int $ttl = 3600): Collection
    {
        return Cache::remember($this->keys['popular_searches'], $ttl, function () use ($limit) {
            // Em um sistema real, você teria uma tabela de logs de busca
            // Por enquanto, retornamos termos de exemplo
            return collect([
                'smartphone',
                'notebook',
                'tablet',
                'fone de ouvido',
                'carregador',
                'capa para celular',
                'película',
                'teclado',
                'mouse',
                'webcam'
            ])->take($limit);
        });
    }

    /**
     * Invalidar cache de produto.
     */
    public function invalidateProductCache(int $productId): void
    {
        $keys = [
            sprintf($this->keys['product_details'], $productId),
            sprintf($this->keys['product_reviews'], $productId),
            sprintf($this->keys['product_images'], $productId),
            $this->keys['products'],
            $this->keys['products_featured'],
            $this->keys['dashboard_stats'],
        ];

        foreach ($keys as $key) {
            Cache::forget($key);
        }

        // Invalidar cache de categoria se o produto tiver categoria
        $product = \LaravelEcommerce\Store\Models\Product::find($productId);
        if ($product && $product->category_id) {
            Cache::forget(sprintf($this->keys['products_category'], $product->category_id));
        }

        // Invalidar cache de marca se o produto tiver marca
        if ($product && $product->brand_id) {
            Cache::forget(sprintf($this->keys['products_brand'], $product->brand_id));
        }
    }

    /**
     * Invalidar cache de categoria.
     */
    public function invalidateCategoryCache(int $categoryId): void
    {
        $keys = [
            sprintf($this->keys['products_category'], $categoryId),
            $this->keys['categories'],
            $this->keys['categories_active'],
            $this->keys['dashboard_stats'],
        ];

        foreach ($keys as $key) {
            Cache::forget($key);
        }
    }

    /**
     * Invalidar cache de marca.
     */
    public function invalidateBrandCache(int $brandId): void
    {
        $keys = [
            sprintf($this->keys['products_brand'], $brandId),
            $this->keys['brands'],
            $this->keys['brands_active'],
            $this->keys['dashboard_stats'],
        ];

        foreach ($keys as $key) {
            Cache::forget($key);
        }
    }

    /**
     * Invalidar cache de cliente.
     */
    public function invalidateCustomerCache(int $customerId): void
    {
        $keys = [
            sprintf($this->keys['customer_details'], $customerId),
            sprintf($this->keys['customer_orders'], $customerId),
            $this->keys['dashboard_stats'],
        ];

        foreach ($keys as $key) {
            Cache::forget($key);
        }
    }

    /**
     * Invalidar cache de pedido.
     */
    public function invalidateOrderCache(int $orderId): void
    {
        $keys = [
            sprintf($this->keys['order_details'], $orderId),
            $this->keys['dashboard_stats'],
        ];

        foreach ($keys as $key) {
            Cache::forget($key);
        }
    }

    /**
     * Invalidar cache de carrinho.
     */
    public function invalidateCartCache(string $cartId): void
    {
        Cache::forget(sprintf($this->keys['cart_items'], $cartId));
    }

    /**
     * Invalidar todo o cache.
     */
    public function invalidateAllCache(): void
    {
        foreach ($this->keys as $key) {
            if (is_array($key)) {
                foreach ($key as $k) {
                    Cache::forget($k);
                }
            } else {
                Cache::forget($key);
            }
        }

        // Limpar cache de padrões específicos
        $this->clearPatternCache('store:*');
    }

    /**
     * Limpar cache por padrão.
     */
    public function clearPatternCache(string $pattern): void
    {
        $cache = Cache::getFacadeRoot();
        $cache->flush(); // Em produção, use tags ou padrões específicos
    }

    /**
     * Obter estatísticas do cache.
     */
    public function getCacheStats(): array
    {
        return [
            'total_keys' => $this->countCacheKeys(),
            'memory_usage' => $this->getCacheMemoryUsage(),
            'hit_rate' => $this->calculateCacheHitRate(),
            'keys_by_type' => $this->getKeysByType(),
        ];
    }

    /**
     * Contar chaves de cache.
     */
    protected function countCacheKeys(): int
    {
        // Em um sistema real, você contaria as chaves do Redis/Memcached
        return Cache::getFacadeRoot()->getStore() instanceof \Illuminate\Cache\RedisStore
            ? $this->countRedisKeys()
            : 0;
    }

    /**
     * Contar chaves Redis.
     */
    protected function countRedisKeys(): int
    {
        try {
            $redis = Cache::getFacadeRoot()->getRedis();
            return $redis->dbsize();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Obter uso de memória do cache.
     */
    protected function getCacheMemoryUsage(): string
    {
        try {
            if (Cache::getFacadeRoot()->getStore() instanceof \Illuminate\Cache\RedisStore) {
                $redis = Cache::getFacadeRoot()->getRedis();
                $info = $redis->info('memory');
                return $info['used_memory_human'] ?? '0MB';
            }
            return 'N/A';
        } catch (\Exception $e) {
            return '0MB';
        }
    }

    /**
     * Calcular taxa de acerto do cache.
     */
    protected function calculateCacheHitRate(): float
    {
        // Em um sistema real, você usaria métricas do Redis/Memcached
        return 85.5; // Valor simulado
    }

    /**
     * Obter chaves por tipo.
     */
    protected function getKeysByType(): array
    {
        return [
            'products' => 45,
            'categories' => 12,
            'customers' => 23,
            'orders' => 34,
            'reports' => 8,
            'settings' => 5,
            'other' => 15,
        ];
    }

    /**
     * Construir chave de cache.
     */
    protected function buildKey(string $key, array $params = []): string
    {
        if (empty($params)) {
            return $this->keys[$key] ?? $key;
        }

        $paramStr = md5(serialize($params));
        return ($this->keys[$key] ?? $key) . ':' . $paramStr;
    }

    /**
     * Definir TTL baseado no tipo de dado.
     */
    public function getTTLForType(string $type): int
    {
        return match ($type) {
            'static' => 86400, // 24 horas
            'dynamic' => 3600, // 1 hora
            'frequent' => 1800, // 30 minutos
            'realtime' => 300, // 5 minutos
            default => 3600,
        };
    }
}