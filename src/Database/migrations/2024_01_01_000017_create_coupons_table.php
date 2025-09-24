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
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique(); // Código do cupom
            $table->string('name'); // Nome do cupom
            $table->text('description')->nullable(); // Descrição do cupom
            $table->enum('type', ['fixed', 'percentage', 'free_shipping']); // Tipo do desconto
            $table->decimal('value', 10, 2); // Valor do desconto
            $table->decimal('minimum_amount', 10, 2)->nullable(); // Valor mínimo para aplicar
            $table->decimal('maximum_discount', 10, 2)->nullable(); // Desconto máximo (para percentual)
            $table->integer('usage_limit')->nullable(); // Limite de uso total
            $table->integer('usage_per_customer')->default(1); // Uso por cliente
            $table->timestamp('starts_at')->nullable(); // Data de início
            $table->timestamp('expires_at')->nullable(); // Data de expiração
            $table->boolean('is_active')->default(true); // Status ativo
            $table->json('applicable_categories')->nullable(); // Categorias aplicáveis
            $table->json('applicable_products')->nullable(); // Produtos aplicáveis
            $table->json('applicable_brands')->nullable(); // Marcas aplicáveis
            $table->json('excluded_categories')->nullable(); // Categorias excluídas
            $table->json('excluded_products')->nullable(); // Produtos excluídos
            $table->json('excluded_brands')->nullable(); // Marcas excluídas
            $table->boolean('first_purchase_only')->default(false); // Apenas primeira compra
            $table->boolean('combine_with_others')->default(false); // Pode combinar com outros
            $table->json('customer_groups')->nullable(); // Grupos de clientes
            $table->integer('used_count')->default(0); // Contador de uso
            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index('code');
            $table->index('type');
            $table->index('is_active');
            $table->index('starts_at');
            $table->index('expires_at');
        });
    }

    /**
     * Reverte as migrações
     */
    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};