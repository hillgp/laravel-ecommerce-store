<?php

namespace LaravelEcommerce\Store\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

class ClearCacheCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'store:clear-cache';

    /**
     * The console command description.
     */
    protected $description = 'Clear all store-related caches';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🧹 Limpando caches da loja...');
        $this->newLine();

        // Clear specific store caches
        $this->clearStoreCaches();

        // Clear Laravel caches
        $this->clearLaravelCaches();

        // Clear application cache
        $this->clearApplicationCache();

        $this->newLine();
        $this->info('✅ Todos os caches foram limpos com sucesso!');

        return self::SUCCESS;
    }

    /**
     * Clear store-specific caches.
     */
    protected function clearStoreCaches(): void
    {
        $this->info('📦 Limpando caches específicos da loja...');

        $storeCaches = [
            'store.products',
            'store.categories',
            'store.brands',
            'store.featured_products',
            'store.popular_products',
            'store.recent_products',
            'store.product_*',
            'store.category_*',
            'store.cart_*',
            'store.customer_*',
            'store.order_*',
            'store.coupon_*',
            'store.review_*',
            'store.settings',
            'store.configuration',
        ];

        foreach ($storeCaches as $pattern) {
            if (str_contains($pattern, '*')) {
                $keys = Cache::get($this->getCacheKeysByPattern($pattern));
                foreach ($keys as $key) {
                    Cache::forget($key);
                    $this->line("  ✓ Removido: {$key}");
                }
            } else {
                Cache::forget($pattern);
                $this->line("  ✓ Removido: {$pattern}");
            }
        }

        $this->newLine();
    }

    /**
     * Clear Laravel system caches.
     */
    protected function clearLaravelCaches(): void
    {
        $this->info('⚡ Limpando caches do Laravel...');

        $commands = [
            'config:clear' => 'Configurações',
            'route:clear' => 'Rotas',
            'view:clear' => 'Views',
        ];

        foreach ($commands as $command => $description) {
            try {
                Artisan::call($command);
                $this->line("  ✓ {$description} limpas");
            } catch (\Exception $e) {
                $this->warn("  ⚠️ Erro ao limpar {$description}: " . $e->getMessage());
            }
        }

        $this->newLine();
    }

    /**
     * Clear application cache.
     */
    protected function clearApplicationCache(): void
    {
        $this->info('💾 Limpando cache da aplicação...');

        try {
            Artisan::call('cache:clear');
            $this->line('  ✓ Cache da aplicação limpo');
        } catch (\Exception $e) {
            $this->warn('  ⚠️ Erro ao limpar cache da aplicação: ' . $e->getMessage());
        }

        // Clear specific cache tags
        $cacheTags = [
            'products',
            'categories',
            'customers',
            'orders',
            'store',
        ];

        foreach ($cacheTags as $tag) {
            try {
                Cache::tags($tag)->flush();
                $this->line("  ✓ Cache da tag '{$tag}' limpo");
            } catch (\Exception $e) {
                $this->warn("  ⚠️ Erro ao limpar cache da tag '{$tag}': " . $e->getMessage());
            }
        }

        $this->newLine();
    }

    /**
     * Get cache keys by pattern.
     */
    protected function getCacheKeysByPattern(string $pattern): array
    {
        $keys = [];
        $pattern = str_replace('*', '.*', $pattern);

        // This is a simplified version - in a real implementation,
        // you might need to use a more sophisticated method to list cache keys
        $allKeys = Cache::get('cache_keys_list', []);

        foreach ($allKeys as $key) {
            if (preg_match("/^{$pattern}$/", $key)) {
                $keys[] = $key;
            }
        }

        return $keys;
    }

    /**
     * Display cache statistics.
     */
    protected function displayCacheStats(): void
    {
        $this->info('📊 Estatísticas do cache:');

        $totalKeys = count(Cache::get('cache_keys_list', []));
        $this->line("  • Total de chaves: {$totalKeys}");

        $storeKeys = count(array_filter(Cache::get('cache_keys_list', []), function ($key) {
            return str_starts_with($key, 'store.');
        }));
        $this->line("  • Chaves da loja: {$storeKeys}");

        $this->newLine();
    }
}