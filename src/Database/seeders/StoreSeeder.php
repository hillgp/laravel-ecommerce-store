<?php

namespace LaravelEcommerce\Store\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class StoreSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🌱 Iniciando seed do Laravel E-commerce Store...');

        // Create admin user
        $this->createAdminUser();

        // Create sample categories
        $this->createSampleCategories();

        // Create sample brands
        $this->createSampleBrands();

        // Create sample products
        $this->createSampleProducts();

        // Create sample customers
        $this->createSampleCustomers();

        // Create sample coupons
        $this->createSampleCoupons();

        $this->command->info('✅ Seed concluído com sucesso!');
    }

    /**
     * Create admin user.
     */
    protected function createAdminUser(): void
    {
        $this->command->info('👤 Criando usuário administrador...');

        DB::table('users')->updateOrInsert(
            ['email' => 'admin@store.com'],
            [
                'name' => 'Administrador da Loja',
                'email' => 'admin@store.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $this->command->info('✅ Usuário administrador criado: admin@store.com / password');
    }

    /**
     * Create sample categories.
     */
    protected function createSampleCategories(): void
    {
        $this->command->info('📂 Criando categorias de exemplo...');

        $categories = [
            [
                'name' => 'Eletrônicos',
                'slug' => 'eletronicos',
                'description' => 'Produtos eletrônicos e tecnologia',
                'is_active' => true,
                'is_featured' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Roupas',
                'slug' => 'roupas',
                'description' => 'Moda e vestuário',
                'is_active' => true,
                'is_featured' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Casa e Jardim',
                'slug' => 'casa-jardim',
                'description' => 'Produtos para casa e jardim',
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 3,
            ],
            [
                'name' => 'Esportes',
                'slug' => 'esportes',
                'description' => 'Artigos esportivos',
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 4,
            ],
            [
                'name' => 'Livros',
                'slug' => 'livros',
                'description' => 'Livros e publicações',
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 5,
            ],
        ];

        foreach ($categories as $category) {
            DB::table('categories')->updateOrInsert(
                ['slug' => $category['slug']],
                array_merge($category, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }

        $this->command->info('✅ Categorias criadas com sucesso!');
    }

    /**
     * Create sample brands.
     */
    protected function createSampleBrands(): void
    {
        $this->command->info('🏷️ Criando marcas de exemplo...');

        $brands = [
            [
                'name' => 'Samsung',
                'slug' => 'samsung',
                'description' => 'Tecnologia Samsung',
                'website' => 'https://samsung.com',
                'is_active' => true,
                'is_featured' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Nike',
                'slug' => 'nike',
                'description' => 'Esportes e moda Nike',
                'website' => 'https://nike.com',
                'is_active' => true,
                'is_featured' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Apple',
                'slug' => 'apple',
                'description' => 'Produtos Apple',
                'website' => 'https://apple.com',
                'is_active' => true,
                'is_featured' => true,
                'sort_order' => 3,
            ],
            [
                'name' => 'Sony',
                'slug' => 'sony',
                'description' => 'Eletrônicos Sony',
                'website' => 'https://sony.com',
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 4,
            ],
        ];

        foreach ($brands as $brand) {
            DB::table('brands')->updateOrInsert(
                ['slug' => $brand['slug']],
                array_merge($brand, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }

        $this->command->info('✅ Marcas criadas com sucesso!');
    }

    /**
     * Create sample products.
     */
    protected function createSampleProducts(): void
    {
        $this->command->info('📱 Criando produtos de exemplo...');

        $electronicsCategory = DB::table('categories')->where('slug', 'eletronicos')->first();
        $clothingCategory = DB::table('categories')->where('slug', 'roupas')->first();
        $samsungBrand = DB::table('brands')->where('slug', 'samsung')->first();
        $nikeBrand = DB::table('brands')->where('slug', 'nike')->first();

        $products = [
            [
                'name' => 'Samsung Galaxy S21',
                'slug' => 'samsung-galaxy-s21',
                'description' => 'Smartphone Samsung Galaxy S21 com câmera de alta qualidade.',
                'short_description' => 'Smartphone premium com câmera excepcional.',
                'sku' => 'SAM-S21-128',
                'price' => 3999.00,
                'compare_price' => 4499.00,
                'cost_price' => 3200.00,
                'track_inventory' => true,
                'stock_quantity' => 50,
                'is_active' => true,
                'is_featured' => true,
                'category_id' => $electronicsCategory->id ?? null,
                'brand_id' => $samsungBrand->id ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Camiseta Nike Dri-FIT',
                'slug' => 'camiseta-nike-dri-fit',
                'description' => 'Camiseta esportiva Nike com tecnologia Dri-FIT.',
                'short_description' => 'Camiseta confortável para atividades físicas.',
                'sku' => 'NIK-TSHIRT-001',
                'price' => 89.90,
                'compare_price' => 120.00,
                'cost_price' => 45.00,
                'track_inventory' => true,
                'stock_quantity' => 100,
                'is_active' => true,
                'is_featured' => false,
                'category_id' => $clothingCategory->id ?? null,
                'brand_id' => $nikeBrand->id ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Fone de Ouvido Sony WH-1000XM4',
                'slug' => 'fone-sony-wh-1000xm4',
                'description' => 'Fone de ouvido com cancelamento de ruído Sony.',
                'short_description' => 'Fone premium com cancelamento de ruído ativo.',
                'sku' => 'SON-WH1000XM4',
                'price' => 1899.00,
                'compare_price' => 2299.00,
                'cost_price' => 1200.00,
                'track_inventory' => true,
                'stock_quantity' => 25,
                'is_active' => true,
                'is_featured' => true,
                'category_id' => $electronicsCategory->id ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($products as $product) {
            DB::table('products')->updateOrInsert(
                ['sku' => $product['sku']],
                $product
            );
        }

        $this->command->info('✅ Produtos criados com sucesso!');
    }

    /**
     * Create sample customers.
     */
    protected function createSampleCustomers(): void
    {
        $this->command->info('👥 Criando clientes de exemplo...');

        $customers = [
            [
                'first_name' => 'João',
                'last_name' => 'Silva',
                'email' => 'joao.silva@email.com',
                'phone' => '(11) 99999-9999',
                'password' => Hash::make('password'),
                'date_of_birth' => '1990-05-15',
                'gender' => 'M',
                'is_active' => true,
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'first_name' => 'Maria',
                'last_name' => 'Santos',
                'email' => 'maria.santos@email.com',
                'phone' => '(11) 88888-8888',
                'password' => Hash::make('password'),
                'date_of_birth' => '1985-08-20',
                'gender' => 'F',
                'is_active' => true,
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($customers as $customer) {
            DB::table('customers')->updateOrInsert(
                ['email' => $customer['email']],
                $customer
            );
        }

        $this->command->info('✅ Clientes criados com sucesso!');
    }

    /**
     * Create sample coupons.
     */
    protected function createSampleCoupons(): void
    {
        $this->command->info('🎫 Criando cupons de exemplo...');

        $coupons = [
            [
                'code' => 'BEMVINDO10',
                'name' => 'Boas Vindas 10%',
                'description' => '10% de desconto na primeira compra',
                'type' => 'percentage',
                'value' => 10.00,
                'minimum_amount' => 100.00,
                'usage_limit' => 100,
                'usage_per_customer' => 1,
                'first_purchase_only' => true,
                'is_active' => true,
                'starts_at' => now(),
                'expires_at' => now()->addMonths(3),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'FRETEGRATIS',
                'name' => 'Frete Grátis',
                'description' => 'Frete grátis em compras acima de R$ 200',
                'type' => 'fixed',
                'value' => 0.00,
                'minimum_amount' => 200.00,
                'usage_limit' => null,
                'usage_per_customer' => 5,
                'is_active' => true,
                'starts_at' => now(),
                'expires_at' => now()->addMonths(6),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'VERAO20',
                'name' => 'Desconto Verão',
                'description' => '20% de desconto em produtos de verão',
                'type' => 'percentage',
                'value' => 20.00,
                'minimum_amount' => 50.00,
                'maximum_discount' => 100.00,
                'usage_limit' => 500,
                'usage_per_customer' => 3,
                'is_active' => true,
                'starts_at' => now(),
                'expires_at' => now()->addDays(30),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($coupons as $coupon) {
            DB::table('coupons')->updateOrInsert(
                ['code' => $coupon['code']],
                $coupon
            );
        }

        $this->command->info('✅ Cupons criados com sucesso!');
    }
}