<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Executa as migrações
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Nome do produto
            $table->string('slug')->unique(); // Slug para URL amigável
            $table->string('sku')->unique(); // SKU do produto
            $table->text('description')->nullable(); // Descrição detalhada
            $table->text('short_description')->nullable(); // Descrição curta
            $table->decimal('price', 10, 2); // Preço base
            $table->decimal('compare_price', 10, 2)->nullable(); // Preço de comparação
            $table->decimal('cost_price', 10, 2)->nullable(); // Preço de custo
            $table->integer('stock_quantity')->default(0); // Quantidade em estoque
            $table->integer('min_stock_quantity')->default(0); // Quantidade mínima em estoque
            $table->boolean('track_stock')->default(true); // Se deve rastrear estoque
            $table->boolean('allow_backorders')->default(false); // Permitir pedidos em espera
            $table->boolean('is_virtual')->default(false); // Produto virtual (download)
            $table->boolean('is_downloadable')->default(false); // Produto baixável
            $table->boolean('is_active')->default(true); // Se o produto está ativo
            $table->boolean('is_featured')->default(false); // Se é produto em destaque
            $table->boolean('is_on_sale')->default(false); // Se está em promoção
            $table->boolean('requires_shipping')->default(true); // Requer envio
            $table->decimal('weight', 8, 3)->nullable(); // Peso em kg
            $table->decimal('length', 8, 2)->nullable(); // Comprimento em cm
            $table->decimal('width', 8, 2)->nullable(); // Largura em cm
            $table->decimal('height', 8, 2)->nullable(); // Altura em cm
            $table->string('unit')->default('un'); // Unidade de medida
            $table->unsignedBigInteger('category_id')->nullable(); // Categoria principal
            $table->unsignedBigInteger('brand_id')->nullable(); // Marca do produto
            $table->integer('sort_order')->default(0); // Ordem de exibição
            $table->integer('view_count')->default(0); // Contador de visualizações
            $table->decimal('rating', 3, 2)->default(0.00); // Avaliação média
            $table->integer('review_count')->default(0); // Número de avaliações
            $table->json('tags')->nullable(); // Tags do produto
            $table->json('attributes')->nullable(); // Atributos personalizados
            $table->json('meta_data')->nullable(); // Metadados adicionais (SEO, etc)
            $table->timestamp('published_at')->nullable(); // Data de publicação
            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index(['is_active', 'is_featured', 'is_on_sale']);
            $table->index(['category_id', 'brand_id']);
            $table->index('sku');
            $table->index('price');
            $table->index('stock_quantity');
            $table->index('sort_order');
            $table->index('view_count');
            $table->index('rating');
            $table->index('published_at');

            // Foreign keys
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('set null');
            $table->foreign('brand_id')->references('id')->on('brands')->onDelete('set null');
        });
    }

    /**
     * Reverte as migrações
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};