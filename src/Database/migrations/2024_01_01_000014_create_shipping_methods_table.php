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
        Schema::create('shipping_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('carrier_id')->constrained()->onDelete('cascade');
            $table->string('name'); // Nome do método (ex: PAC, SEDEX, Express)
            $table->string('code')->unique(); // Código único do método
            $table->text('description')->nullable();
            $table->decimal('base_cost', 10, 2); // Custo base
            $table->decimal('cost_per_kg', 10, 2)->default(0); // Custo por kg adicional
            $table->decimal('cost_per_km', 10, 2)->default(0); // Custo por km
            $table->integer('estimated_days_min')->nullable(); // Prazo mínimo em dias
            $table->integer('estimated_days_max')->nullable(); // Prazo máximo em dias
            $table->decimal('min_weight', 8, 3)->nullable();
            $table->decimal('max_weight', 8, 3)->nullable();
            $table->decimal('min_value', 10, 2)->nullable();
            $table->decimal('max_value', 10, 2)->nullable();
            $table->boolean('requires_insurance')->default(false);
            $table->decimal('insurance_rate', 5, 4)->default(0); // Taxa de seguro (percentual)
            $table->boolean('is_active')->default(true);
            $table->json('supported_postal_codes')->nullable(); // CEPs atendidos
            $table->json('excluded_postal_codes')->nullable(); // CEPs não atendidos
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['carrier_id', 'is_active']);
            $table->index('code');
            $table->index('is_active');
            $table->index('sort_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipping_methods');
    }
};