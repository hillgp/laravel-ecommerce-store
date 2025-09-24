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
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id'); // Pedido
            $table->unsignedBigInteger('product_id'); // Produto
            $table->unsignedBigInteger('product_variant_id')->nullable(); // Variação do produto
            $table->string('product_name'); // Nome do produto no momento do pedido
            $table->string('product_sku'); // SKU do produto no momento do pedido
            $table->integer('quantity'); // Quantidade
            $table->decimal('price', 10, 2); // Preço unitário no momento do pedido
            $table->decimal('total', 10, 2); // Preço total (price * quantity)
            $table->decimal('discount_amount', 10, 2)->default(0); // Desconto no item
            $table->decimal('tax_amount', 10, 2)->default(0); // Impostos no item
            $table->json('options')->nullable(); // Opções do produto (personalização)
            $table->json('attributes')->nullable(); // Atributos do produto
            $table->json('meta_data')->nullable(); // Metadados adicionais
            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index(['order_id', 'product_id']);
            $table->index('product_variant_id');

            // Foreign keys
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('restrict');
            $table->foreign('product_variant_id')->references('id')->on('product_variants')->onDelete('set null');
        });
    }

    /**
     * Reverte as migrações
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};