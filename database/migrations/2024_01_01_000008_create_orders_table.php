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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique(); // Número do pedido
            $table->unsignedBigInteger('customer_id'); // Cliente
            $table->string('status')->default('pending'); // Status do pedido
            $table->string('payment_status')->default('pending'); // Status do pagamento
            $table->string('shipping_status')->default('not_shipped'); // Status do envio
            $table->decimal('subtotal', 10, 2); // Subtotal dos produtos
            $table->decimal('discount_amount', 10, 2)->default(0); // Valor do desconto
            $table->decimal('shipping_cost', 10, 2)->default(0); // Custo do frete
            $table->decimal('tax_amount', 10, 2)->default(0); // Valor dos impostos
            $table->decimal('total', 10, 2); // Total do pedido
            $table->string('currency', 3)->default('BRL'); // Moeda
            $table->string('payment_method')->nullable(); // Método de pagamento
            $table->string('payment_gateway')->nullable(); // Gateway de pagamento
            $table->string('transaction_id')->nullable(); // ID da transação
            $table->json('payment_data')->nullable(); // Dados do pagamento
            $table->string('shipping_method')->nullable(); // Método de envio
            $table->string('tracking_number')->nullable(); // Número de rastreamento
            $table->string('tracking_url')->nullable(); // URL de rastreamento
            $table->json('shipping_data')->nullable(); // Dados do envio
            $table->unsignedBigInteger('billing_address_id')->nullable(); // Endereço de cobrança
            $table->unsignedBigInteger('shipping_address_id')->nullable(); // Endereço de entrega
            $table->text('notes')->nullable(); // Observações do pedido
            $table->text('internal_notes')->nullable(); // Observações internas
            $table->string('coupon_code')->nullable(); // Código do cupom
            $table->json('meta_data')->nullable(); // Metadados adicionais
            $table->timestamp('confirmed_at')->nullable(); // Data de confirmação
            $table->timestamp('shipped_at')->nullable(); // Data de envio
            $table->timestamp('delivered_at')->nullable(); // Data de entrega
            $table->timestamp('cancelled_at')->nullable(); // Data de cancelamento
            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index('order_number');
            $table->index('status');
            $table->index('payment_status');
            $table->index('shipping_status');
            $table->index('customer_id');
            $table->index('transaction_id');
            $table->index('tracking_number');
            $table->index(['confirmed_at', 'shipped_at', 'delivered_at']);

            // Foreign keys
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            $table->foreign('billing_address_id')->references('id')->on('customer_addresses')->onDelete('set null');
            $table->foreign('shipping_address_id')->references('id')->on('customer_addresses')->onDelete('set null');
        });
    }

    /**
     * Reverte as migrações
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};