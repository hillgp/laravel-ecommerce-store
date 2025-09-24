<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('shipping_calculations', function (Blueprint $table) {
            $table->id();
            $table->string('session_id')->nullable(); // Para usuários não logados
            $table->foreignId('customer_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('cart_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('order_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('postal_code_from', 9); // CEP de origem
            $table->string('postal_code_to', 9); // CEP de destino
            $table->decimal('total_weight', 8, 3); // Peso total em kg
            $table->decimal('total_value', 10, 2); // Valor total dos produtos
            $table->decimal('total_volume', 8, 3)->nullable(); // Volume total em m³
            $table->json('dimensions')->nullable(); // Dimensões dos produtos
            $table->foreignId('selected_method_id')->nullable()->constrained('shipping_methods');
            $table->decimal('calculated_cost', 10, 2)->nullable(); // Custo calculado
            $table->integer('estimated_days')->nullable(); // Prazo estimado
            $table->string('tracking_code')->nullable(); // Código de rastreamento
            $table->json('calculation_details')->nullable(); // Detalhes do cálculo
            $table->json('api_response')->nullable(); // Resposta da API da transportadora
            $table->timestamp('calculated_at');
            $table->timestamps();

            $table->index(['session_id', 'calculated_at']);
            $table->index(['customer_id', 'calculated_at']);
            $table->index('postal_code_from');
            $table->index('postal_code_to');
            $table->index('calculated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipping_calculations');
    }
};