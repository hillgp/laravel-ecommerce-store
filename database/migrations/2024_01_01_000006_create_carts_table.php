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
        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->string('session_id')->nullable(); // ID da sessão para carrinhos de convidados
            $table->unsignedBigInteger('customer_id')->nullable(); // Cliente logado
            $table->string('coupon_code')->nullable(); // Código do cupom aplicado
            $table->decimal('subtotal', 10, 2)->default(0); // Subtotal dos itens
            $table->decimal('discount_amount', 10, 2)->default(0); // Valor do desconto
            $table->decimal('shipping_cost', 10, 2)->default(0); // Custo do frete
            $table->decimal('tax_amount', 10, 2)->default(0); // Valor dos impostos
            $table->decimal('total', 10, 2)->default(0); // Total do carrinho
            $table->integer('items_count')->default(0); // Número de itens
            $table->json('meta_data')->nullable(); // Metadados adicionais
            $table->timestamp('last_activity')->useCurrent(); // Última atividade
            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index(['session_id', 'customer_id']);
            $table->index('last_activity');

            // Foreign key
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
        });
    }

    /**
     * Reverte as migrações
     */
    public function down(): void
    {
        Schema::dropIfExists('carts');
    }
};