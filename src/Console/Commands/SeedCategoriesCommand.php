<?php

namespace LaravelEcommerce\Store\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SeedCategoriesCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'store:seed-categories {--count=10 : Number of categories to create}';

    /**
     * The console command description.
     */
    protected $description = 'Seed sample categories for the store';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $count = $this->option('count');

        $this->info("🌱 Criando {$count} categorias de exemplo...");

        $categories = [
            ['name' => 'Eletrônicos', 'description' => 'Produtos eletrônicos e tecnologia'],
            ['name' => 'Roupas', 'description' => 'Moda e vestuário'],
            ['name' => 'Casa e Jardim', 'description' => 'Produtos para casa e jardim'],
            ['name' => 'Esportes', 'description' => 'Artigos esportivos'],
            ['name' => 'Livros', 'description' => 'Livros e publicações'],
            ['name' => 'Automotivo', 'description' => 'Acessórios automotivos'],
            ['name' => 'Beleza', 'description' => 'Produtos de beleza e cuidados pessoais'],
            ['name' => 'Brinquedos', 'description' => 'Brinquedos e jogos'],
            ['name' => 'Alimentos', 'description' => 'Alimentos e bebidas'],
            ['name' => 'Saúde', 'description' => 'Produtos de saúde e bem-estar'],
            ['name' => 'Ferramentas', 'description' => 'Ferramentas e equipamentos'],
            ['name' => 'Móveis', 'description' => 'Móveis e decoração'],
            ['name' => 'Instrumentos Musicais', 'description' => 'Instrumentos musicais'],
            ['name' => 'Jardinagem', 'description' => 'Produtos para jardinagem'],
            ['name' => 'Pet Shop', 'description' => 'Produtos para animais de estimação'],
        ];

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        for ($i = 0; $i < $count; $i++) {
            $category = $categories[$i % count($categories)];

            DB::table('categories')->updateOrInsert(
                ['slug' => \Illuminate\Support\Str::slug($category['name'])],
                [
                    'name' => $category['name'],
                    'slug' => \Illuminate\Support\Str::slug($category['name']),
                    'description' => $category['description'],
                    'is_active' => true,
                    'is_featured' => rand(0, 1),
                    'sort_order' => $i + 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("✅ {$count} categorias criadas com sucesso!");

        return self::SUCCESS;
    }
}