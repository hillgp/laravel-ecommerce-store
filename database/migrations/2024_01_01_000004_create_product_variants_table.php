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
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id'); // Produto pai
            $table->string('name'); // Nome da variação (ex: "Camiseta Vermelha - G")
            $table->string('sku')->unique(); // SKU da variação
            $table->decimal('price', 10, 2)->nullable(); // Preço específico da variação
            $table->decimal('compare_price', 10, 2)->nullable(); // Preço de comparação da variação
            $table->decimal('cost_price', 10, 2)->nullable(); // Preço de custo da variação
            $table->integer('stock_quantity')->default(0); // Quantidade em estoque da variação
            $table->integer('min_stock_quantity')->default(0); // Quantidade mínima da variação
            $table->boolean('track_stock')->default(true); // Se deve rastrear estoque da variação
            $table->boolean('allow_backorders')->default(false); // Permitir pedidos em espera
            $table->decimal('weight', 8, 3)->nullable(); // Peso específico da variação
            $table->decimal('length', 8, 2)->nullable(); // Dimensões específicas
            $table->decimal('width', 8, 2)->nullable();
            $table->decimal('height', 8, 2)->nullable();
            $table->string('barcode')->nullable(); // Código de barras da variação
            $table->string('image')->nullable(); // Imagem específica da variação
            $table->json('options')->nullable(); // Opções da variação (cor, tamanho, etc)
            $table->json('attributes')->nullable(); // Atributos específicos da variação
            $table->boolean('is_active')->default(true); // Se a variação está ativa
            $table->boolean('is_default')->default(false); // Se é a variação padrão
            $table->integer('sort_order')->default(0); // Ordem de exibição
            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index('product_id');
            $table->index('sku');
            $table->index('barcode');
            $table->index(['is_active', 'is_default']);
            $table->index('stock_quantity');
            $table->index('sort_order');

            // Foreign key
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
        });
    }

    /**
     * Reverte as migrações
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};