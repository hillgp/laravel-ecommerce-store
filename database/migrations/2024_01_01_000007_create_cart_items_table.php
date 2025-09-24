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
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cart_id'); // Carrinho
            $table->unsignedBigInteger('product_id'); // Produto
            $table->unsignedBigInteger('product_variant_id')->nullable(); // Variação do produto
            $table->integer('quantity'); // Quantidade
            $table->decimal('price', 10, 2); // Preço unitário no momento da adição
            $table->decimal('total', 10, 2); // Preço total (price * quantity)
            $table->json('options')->nullable(); // Opções adicionais (personalização)
            $table->json('meta_data')->nullable(); // Metadados adicionais
            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index(['cart_id', 'product_id']);
            $table->index('product_variant_id');

            // Foreign keys
            $table->foreign('cart_id')->references('id')->on('carts')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('product_variant_id')->references('id')->on('product_variants')->onDelete('set null');

            // Unique constraint para evitar itens duplicados no mesmo carrinho
            $table->unique(['cart_id', 'product_id', 'product_variant_id']);
        });
    }

    /**
     * Reverte as migrações
     */
    public function down(): void
    {
        Schema::dropIfExists('cart_items');
    }
};