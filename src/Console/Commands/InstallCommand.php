<?php

namespace LaravelEcommerce\Store\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'store:install {--force : Force the operation to run when in production}';

    /**
     * The console command description.
     */
    protected $description = 'Install the Laravel E-commerce Store package';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🚀 Instalando Laravel E-commerce Store...');
        $this->newLine();

        // Check if we're in production and force flag is not provided
        if ($this->laravel->environment('production') && !$this->option('force')) {
            $this->error('A aplicação está em modo produção!');
            $this->error('Use --force para forçar a instalação em produção.');
            return self::FAILURE;
        }

        // Step 1: Publish configuration
        $this->info('📋 Publicando configurações...');
        Artisan::call('vendor:publish', [
            '--provider' => 'LaravelEcommerce\Store\StoreServiceProvider',
            '--tag' => 'store-config',
            '--force' => true,
        ]);
        $this->info('✅ Configurações publicadas com sucesso!');
        $this->newLine();

        // Step 2: Publish migrations
        $this->info('🗄️ Publicando migrations...');
        Artisan::call('vendor:publish', [
            '--provider' => 'LaravelEcommerce\Store\StoreServiceProvider',
            '--tag' => 'store-migrations',
            '--force' => true,
        ]);
        $this->info('✅ Migrations publicadas com sucesso!');
        $this->newLine();

        // Step 3: Run migrations
        $this->info('🏃 Executando migrations...');
        try {
            Artisan::call('migrate', [], $this->output);
            $this->info('✅ Migrations executadas com sucesso!');
        } catch (\Exception $e) {
            $this->warn('⚠️ Erro ao executar migrations: ' . $e->getMessage());
            $this->warn('Verifique se o banco de dados está configurado corretamente.');
        }
        $this->newLine();

        // Step 4: Publish assets
        $this->info('🎨 Publicando assets...');
        Artisan::call('vendor:publish', [
            '--provider' => 'LaravelEcommerce\Store\StoreServiceProvider',
            '--tag' => 'store-assets',
            '--force' => true,
        ]);
        $this->info('✅ Assets publicados com sucesso!');
        $this->newLine();

        // Step 5: Publish views
        $this->info('👁️ Publicando views...');
        Artisan::call('vendor:publish', [
            '--provider' => 'LaravelEcommerce\Store\StoreServiceProvider',
            '--tag' => 'store-views',
            '--force' => true,
        ]);
        $this->info('✅ Views publicadas com sucesso!');
        $this->newLine();

        // Step 6: Create storage directories
        $this->info('📁 Criando diretórios de storage...');
        $this->createStorageDirectories();
        $this->info('✅ Diretórios de storage criados com sucesso!');
        $this->newLine();

        // Step 7: Create symbolic links
        $this->info('🔗 Criando links simbólicos...');
        try {
            Artisan::call('storage:link', [], $this->output);
            $this->info('✅ Links simbólicos criados com sucesso!');
        } catch (\Exception $e) {
            $this->warn('⚠️ Erro ao criar links simbólicos: ' . $e->getMessage());
        }
        $this->newLine();

        // Step 8: Seed database
        $this->info('🌱 Executando seeds...');
        try {
            Artisan::call('db:seed', [
                '--class' => 'LaravelEcommerce\\Store\\Database\\Seeders\\StoreSeeder',
            ], $this->output);
            $this->info('✅ Seeds executados com sucesso!');
        } catch (\Exception $e) {
            $this->warn('⚠️ Erro ao executar seeds: ' . $e->getMessage());
            $this->warn('Você pode executar os seeds manualmente mais tarde.');
        }
        $this->newLine();

        // Step 9: Clear caches
        $this->info('🧹 Limpando caches...');
        Artisan::call('config:clear');
        Artisan::call('route:clear');
        Artisan::call('view:clear');
        Artisan::call('cache:clear');
        $this->info('✅ Caches limpos com sucesso!');
        $this->newLine();

        // Step 10: Display success message
        $this->info('🎉 Laravel E-commerce Store instalado com sucesso!');
        $this->newLine();

        $this->info('📝 Próximos passos:');
        $this->line('1. Configure as opções de pagamento em config/store.php');
        $this->line('2. Configure os métodos de envio em config/store.php');
        $this->line('3. Configure as notificações por email em config/store.php');
        $this->line('4. Personalize as views em resources/views/vendor/store/');
        $this->line('5. Configure os assets (CSS/JS) conforme necessário');
        $this->line('6. Execute os testes com: php artisan test');
        $this->newLine();

        $this->info('📚 Documentação:');
        $this->line('Para mais informações, consulte a documentação em:');
        $this->line('https://github.com/your-repo/laravel-ecommerce-store');
        $this->newLine();

        $this->info('💡 Comandos disponíveis:');
        $this->line('php artisan store:seed-categories    - Popular categorias');
        $this->line('php artisan store:seed-products     - Popular produtos');
        $this->line('php artisan store:seed-customers    - Popular clientes');
        $this->line('php artisan store:clear-cache       - Limpar cache da loja');
        $this->line('php artisan store:generate-sitemap  - Gerar sitemap');
        $this->newLine();

        return self::SUCCESS;
    }

    /**
     * Create storage directories.
     */
    protected function createStorageDirectories(): void
    {
        $directories = [
            'app/public/products',
            'app/public/products/thumbnails',
            'app/public/products/medium',
            'app/public/products/large',
            'app/public/categories',
            'app/public/categories/icons',
            'app/public/brands',
            'app/public/customers',
            'app/public/reviews',
            'app/public/banners',
            'app/public/temp',
        ];

        foreach ($directories as $directory) {
            $path = storage_path($directory);

            if (!File::exists($path)) {
                File::makeDirectory($path, 0755, true);
                $this->line("✓ Criado: {$directory}");
            }
        }

        // Create .gitkeep files
        $gitkeepFiles = [
            'app/public/products/.gitkeep',
            'app/public/products/thumbnails/.gitkeep',
            'app/public/products/medium/.gitkeep',
            'app/public/products/large/.gitkeep',
            'app/public/categories/.gitkeep',
            'app/public/categories/icons/.gitkeep',
            'app/public/brands/.gitkeep',
            'app/public/customers/.gitkeep',
            'app/public/reviews/.gitkeep',
            'app/public/banners/.gitkeep',
            'app/public/temp/.gitkeep',
        ];

        foreach ($gitkeepFiles as $file) {
            $path = storage_path($file);

            if (!File::exists($path)) {
                File::put($path, '');
            }
        }
    }
}