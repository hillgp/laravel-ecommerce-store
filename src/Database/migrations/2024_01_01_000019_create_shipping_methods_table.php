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
        Schema::create('shipping_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Nome do método de envio
            $table->string('code')->unique(); // Código único do método
            $table->text('description')->nullable(); // Descrição do método
            $table->enum('type', ['fixed', 'weight_based', 'price_based', 'distance_based', 'free']); // Tipo de cálculo
            $table->decimal('base_cost', 10, 2)->default(0); // Custo base
            $table->decimal('cost_per_kg', 10, 2)->nullable(); // Custo por kg (para weight_based)
            $table->decimal('cost_per_km', 10, 2)->nullable(); // Custo por km (para distance_based)
            $table->decimal('cost_percentage', 5, 2)->nullable(); // Percentual sobre o valor (para price_based)
            $table->decimal('minimum_amount', 10, 2)->nullable(); // Valor mínimo para aplicar
            $table->decimal('maximum_amount', 10, 2)->nullable(); // Valor máximo para aplicar
            $table->decimal('minimum_weight', 10, 3)->nullable(); // Peso mínimo (kg)
            $table->decimal('maximum_weight', 10, 3)->nullable(); // Peso máximo (kg)
            $table->integer('estimated_days_min')->nullable(); // Prazo mínimo de entrega (dias)
            $table->integer('estimated_days_max')->nullable(); // Prazo máximo de entrega (dias)
            $table->boolean('requires_tracking')->default(false); // Requer código de rastreio
            $table->boolean('is_insured')->default(false); // Possui seguro
            $table->boolean('is_active')->default(true); // Status ativo
            $table->json('configuration')->nullable(); // Configurações específicas do método
            $table->integer('sort_order')->default(0); // Ordem de exibição
            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index('code');
            $table->index('type');
            $table->index('is_active');
            $table->index('sort_order');
        });
    }

    /**
     * Reverte as migrações
     */
    public function down(): void
    {
        Schema::dropIfExists('shipping_methods');
    }
};