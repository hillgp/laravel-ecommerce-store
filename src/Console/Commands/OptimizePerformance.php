<?php

namespace LaravelEcommerce\Store\Console\Commands;

use LaravelEcommerce\Store\Services\CacheService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

class OptimizePerformance extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'store:optimize
                            {--action=cache : Action to perform (cache|clear|stats|optimize)}
                            {--type=all : Type of cache to manage (all|products|categories|customers|orders|reports)}
                            {--force : Force optimization without confirmation}';

    /**
     * The console command description.
     */
    protected $description = 'Optimize store performance with caching and database optimizations';

    protected CacheService $cacheService;

    /**
     * Create a new command instance.
     */
    public function __construct(CacheService $cacheService)
    {
        parent::__construct();
        $this->cacheService = $cacheService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $action = $this->option('action');
        $type = $this->option('type');
        $force = $this->option('force');

        switch ($action) {
            case 'cache':
                return $this->buildCache($type, $force);
            case 'clear':
                return $this->clearCache($type, $force);
            case 'stats':
                return $this->showCacheStats();
            case 'optimize':
                return $this->optimizeDatabase();
            default:
                $this->error("Unknown action: {$action}");
                return Command::FAILURE;
        }
    }

    /**
     * Build cache for store data.
     */
    protected function buildCache(string $type, bool $force): int
    {
        if (!$force && !$this->confirm('This will rebuild the cache. Continue?')) {
            return Command::SUCCESS;
        }

        $this->info('Building cache...');

        $bar = $this->output->createProgressBar(10);
        $bar->start();

        try {
            switch ($type) {
                case 'products':
                    $this->buildProductsCache();
                    break;
                case 'categories':
                    $this->buildCategoriesCache();
                    break;
                case 'customers':
                    $this->buildCustomersCache();
                    break;
                case 'orders':
                    $this->buildOrdersCache();
                    break;
                case 'reports':
                    $this->buildReportsCache();
                    break;
                case 'all':
                    $this->buildAllCache();
                    break;
                default:
                    $this->error("Unknown cache type: {$type}");
                    return Command::FAILURE;
            }

            $bar->finish();
            $this->newLine(2);
            $this->info('Cache built successfully!');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $bar->finish();
            $this->error('Error building cache: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Clear cache.
     */
    protected function clearCache(string $type, bool $force): int
    {
        if (!$force && !$this->confirm('This will clear the cache. Continue?')) {
            return Command::SUCCESS;
        }

        $this->info('Clearing cache...');

        try {
            switch ($type) {
                case 'products':
                    $this->clearProductsCache();
                    break;
                case 'categories':
                    $this->clearCategoriesCache();
                    break;
                case 'customers':
                    $this->clearCustomersCache();
                    break;
                case 'orders':
                    $this->clearOrdersCache();
                    break;
                case 'reports':
                    $this->clearReportsCache();
                    break;
                case 'all':
                    $this->clearAllCache();
                    break;
                default:
                    $this->error("Unknown cache type: {$type}");
                    return Command::FAILURE;
            }

            $this->info('Cache cleared successfully!');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Error clearing cache: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Show cache statistics.
     */
    protected function showCacheStats(): int
    {
        $this->info('Cache Statistics:');
        $this->line('================');

        $stats = $this->cacheService->getCacheStats();

        $this->table([
            'Metric',
            'Value'
        ], [
            ['Total Keys', number_format($stats['total_keys'])],
            ['Memory Usage', $stats['memory_usage']],
            ['Hit Rate', number_format($stats['hit_rate'], 2) . '%'],
            ['Keys by Type', implode(', ', array_map(
                fn($type, $count) => "{$type}: {$count}",
                array_keys($stats['keys_by_type']),
                $stats['keys_by_type']
            ))],
        ]);

        return Command::SUCCESS;
    }

    /**
     * Optimize database.
     */
    protected function optimizeDatabase(): int
    {
        $this->info('Optimizing database...');

        $bar = $this->output->createProgressBar(5);
        $bar->start();

        try {
            // Optimize tables
            $bar->setMessage('Optimizing tables...');
            $this->optimizeTables();
            $bar->advance();

            // Update statistics
            $bar->setMessage('Updating statistics...');
            $this->updateTableStatistics();
            $bar->advance();

            // Clean up old data
            $bar->setMessage('Cleaning up old data...');
            $this->cleanupOldData();
            $bar->advance();

            // Rebuild indexes
            $bar->setMessage('Rebuilding indexes...');
            $this->rebuildIndexes();
            $bar->advance();

            // Clear cache
            $bar->setMessage('Clearing cache...');
            $this->clearAllCache();
            $bar->advance();

            $bar->finish();
            $this->newLine(2);
            $this->info('Database optimized successfully!');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $bar->finish();
            $this->error('Error optimizing database: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Build products cache.
     */
    protected function buildProductsCache(): void
    {
        $this->info('Building products cache...');

        // Cache de produtos em destaque
        $this->cacheService->getFeaturedProducts(12, 0);

        // Cache de produtos por categoria
        $categories = \LaravelEcommerce\Store\Models\Category::where('is_active', true)->get();
        foreach ($categories as $category) {
            $this->cacheService->getProductsByCategory($category->id, 20, 0);
        }

        // Cache de produtos por marca
        $brands = \LaravelEcommerce\Store\Models\Brand::where('is_active', true)->get();
        foreach ($brands as $brand) {
            $this->cacheService->getProductsByBrand($brand->id, 20, 0);
        }

        // Cache de detalhes dos produtos
        $products = \LaravelEcommerce\Store\Models\Product::where('is_active', true)->limit(100)->get();
        foreach ($products as $product) {
            $this->cacheService->getProductDetails($product->id, 0);
        }
    }

    /**
     * Build categories cache.
     */
    protected function buildCategoriesCache(): void
    {
        $this->cacheService->getCategories(true, 0);
        $this->cacheService->getCategories(false, 0);
    }

    /**
     * Build customers cache.
     */
    protected function buildCustomersCache(): void
    {
        // Cache de configurações
        $this->cacheService->getSettings(0);
        $this->cacheService->getPaymentMethods(0);
        $this->cacheService->getShippingMethods(0);
    }

    /**
     * Build orders cache.
     */
    protected function buildOrdersCache(): void
    {
        // Cache de estatísticas do dashboard
        $this->cacheService->getDashboardStats(0);
    }

    /**
     * Build reports cache.
     */
    protected function buildReportsCache(): void
    {
        // Cache de relatórios mais acessados
        $this->cacheService->salesReport(['group_by' => 'day'], 0);
        $this->cacheService->topProductsReport(['limit' => 20], 0);
        $this->cacheService->categoryReport([], 0);
    }

    /**
     * Build all cache.
     */
    protected function buildAllCache(): void
    {
        $this->buildProductsCache();
        $this->buildCategoriesCache();
        $this->buildCustomersCache();
        $this->buildOrdersCache();
        $this->buildReportsCache();
    }

    /**
     * Clear products cache.
     */
    protected function clearProductsCache(): void
    {
        $this->clearPatternCache('store:products*');
        $this->clearPatternCache('store:product*');
    }

    /**
     * Clear categories cache.
     */
    protected function clearCategoriesCache(): void
    {
        $this->clearPatternCache('store:categories*');
    }

    /**
     * Clear customers cache.
     */
    protected function clearCustomersCache(): void
    {
        $this->clearPatternCache('store:customer*');
        $this->clearPatternCache('store:settings');
        $this->clearPatternCache('store:payment*');
        $this->clearPatternCache('store:shipping*');
    }

    /**
     * Clear orders cache.
     */
    protected function clearOrdersCache(): void
    {
        $this->clearPatternCache('store:order*');
        $this->clearPatternCache('store:dashboard*');
    }

    /**
     * Clear reports cache.
     */
    protected function clearReportsCache(): void
    {
        $this->clearPatternCache('store:reports*');
    }

    /**
     * Clear all cache.
     */
    protected function clearAllCache(): void
    {
        $this->clearProductsCache();
        $this->clearCategoriesCache();
        $this->clearCustomersCache();
        $this->clearOrdersCache();
        $this->clearReportsCache();
        $this->clearPatternCache('store:*');
    }

    /**
     * Clear cache by pattern.
     */
    protected function clearPatternCache(string $pattern): void
    {
        $cache = Cache::getFacadeRoot();
        $cache->flush(); // Em produção, use tags ou padrões específicos
    }

    /**
     * Optimize database tables.
     */
    protected function optimizeTables(): void
    {
        $tables = [
            'products',
            'categories',
            'brands',
            'customers',
            'orders',
            'order_items',
            'carts',
            'cart_items',
            'product_reviews',
            'notifications',
            'notification_templates',
            'notification_settings',
        ];

        foreach ($tables as $table) {
            try {
                DB::statement("OPTIMIZE TABLE {$table}");
            } catch (\Exception $e) {
                $this->warn("Could not optimize table {$table}: " . $e->getMessage());
            }
        }
    }

    /**
     * Update table statistics.
     */
    protected function updateTableStatistics(): void
    {
        try {
            DB::statement('ANALYZE TABLE products, categories, brands, customers, orders');
        } catch (\Exception $e) {
            $this->warn('Could not update table statistics: ' . $e->getMessage());
        }
    }

    /**
     * Clean up old data.
     */
    protected function cleanupOldData(): void
    {
        // Limpar carrinhos abandonados (mais de 30 dias)
        $cutoffDate = now()->subDays(30);
        \LaravelEcommerce\Store\Models\Cart::where('updated_at', '<', $cutoffDate)
            ->whereDoesntHave('items')
            ->delete();

        // Limpar notificações antigas (mais de 90 dias)
        \LaravelEcommerce\Store\Models\Notification::where('created_at', '<', now()->subDays(90))
            ->delete();

        // Limpar logs antigos (mais de 30 dias)
        if (class_exists('\LaravelEcommerce\Store\Models\SystemLog')) {
            \LaravelEcommerce\Store\Models\SystemLog::where('created_at', '<', now()->subDays(30))
                ->delete();
        }
    }

    /**
     * Rebuild indexes.
     */
    protected function rebuildIndexes(): void
    {
        try {
            // Recriar índices para produtos
            DB::statement('ALTER TABLE products DROP INDEX idx_products_category_active, ADD INDEX idx_products_category_active (category_id, is_active)');
            DB::statement('ALTER TABLE products DROP INDEX idx_products_brand_active, ADD INDEX idx_products_brand_active (brand_id, is_active)');
            DB::statement('ALTER TABLE products DROP INDEX idx_products_featured_active, ADD INDEX idx_products_featured_active (is_featured, is_active)');

            // Recriar índices para pedidos
            DB::statement('ALTER TABLE orders DROP INDEX idx_orders_status_created, ADD INDEX idx_orders_status_created (status, created_at)');
            DB::statement('ALTER TABLE orders DROP INDEX idx_orders_customer_status, ADD INDEX idx_orders_customer_status (customer_id, status)');

            // Recriar índices para clientes
            DB::statement('ALTER TABLE customers DROP INDEX idx_customers_email, ADD INDEX idx_customers_email (email)');
            DB::statement('ALTER TABLE customers DROP INDEX idx_customers_active, ADD INDEX idx_customers_active (is_active)');

        } catch (\Exception $e) {
            $this->warn('Could not rebuild indexes: ' . $e->getMessage());
        }
    }
}