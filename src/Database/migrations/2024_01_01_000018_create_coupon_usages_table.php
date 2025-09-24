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
        Schema::create('coupon_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coupon_id')->constrained()->onDelete('cascade');
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->foreignId('order_id')->nullable()->constrained()->onDelete('set null');
            $table->string('coupon_code'); // Código usado (para histórico)
            $table->decimal('discount_amount', 10, 2); // Valor do desconto aplicado
            $table->timestamp('used_at'); // Data de uso
            $table->timestamps();

            // Índices
            $table->index(['coupon_id', 'customer_id']);
            $table->index('coupon_code');
            $table->index('used_at');

            // Constraint única para evitar uso duplo do mesmo cupom pelo mesmo cliente
            $table->unique(['coupon_id', 'customer_id', 'order_id']);
        });
    }

    /**
     * Reverte as migrações
     */
    public function down(): void
    {
        Schema::dropIfExists('coupon_usages');
    }
};