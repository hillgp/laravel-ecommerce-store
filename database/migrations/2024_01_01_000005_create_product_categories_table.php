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
        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id'); // Produto
            $table->unsignedBigInteger('category_id'); // Categoria
            $table->boolean('is_primary')->default(false); // Se é a categoria principal
            $table->integer('sort_order')->default(0); // Ordem de exibição
            $table->timestamps();

            // Índices
            $table->index(['product_id', 'category_id']);
            $table->index(['category_id', 'is_primary']);
            $table->index('sort_order');

            // Foreign keys
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');

            // Unique constraint para evitar duplicatas
            $table->unique(['product_id', 'category_id']);
        });
    }

    /**
     * Reverte as migrações
     */
    public function down(): void
    {
        Schema::dropIfExists('product_categories');
    }
};