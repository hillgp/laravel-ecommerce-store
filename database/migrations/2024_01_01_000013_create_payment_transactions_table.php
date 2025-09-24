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
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_id')->unique(); // ID único da transação
            $table->unsignedBigInteger('order_id'); // Pedido relacionado
            $table->string('gateway'); // Gateway usado (stripe, mercadopago, pagseguro)
            $table->string('status')->default('pending'); // Status da transação
            $table->string('type')->default('payment'); // Tipo: payment, refund, chargeback
            $table->decimal('amount', 10, 2); // Valor da transação
            $table->string('currency', 3)->default('BRL'); // Moeda
            $table->string('payment_method')->nullable(); // Método de pagamento
            $table->string('external_id')->nullable(); // ID no gateway externo
            $table->json('gateway_response')->nullable(); // Resposta completa do gateway
            $table->json('meta_data')->nullable(); // Metadados adicionais
            $table->text('notes')->nullable(); // Observações
            $table->timestamp('processed_at')->nullable(); // Data de processamento
            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index('transaction_id');
            $table->index('order_id');
            $table->index('gateway');
            $table->index('status');
            $table->index('type');
            $table->index('external_id');
            $table->index('processed_at');

            // Foreign key
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
        });
    }

    /**
     * Reverte as migrações
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};