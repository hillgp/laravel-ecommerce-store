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
        Schema::create('shipping_zones', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Nome da zona
            $table->string('code')->unique(); // Código único da zona
            $table->text('description')->nullable(); // Descrição da zona
            $table->enum('type', ['country', 'state', 'city', 'zip_range', 'custom']); // Tipo de zona
            $table->string('country_code', 3)->nullable(); // Código do país (ISO 3166-1 alpha-3)
            $table->string('state_code', 10)->nullable(); // Código do estado
            $table->string('city')->nullable(); // Nome da cidade
            $table->string('zip_start')->nullable(); // CEP inicial
            $table->string('zip_end')->nullable(); // CEP final
            $table->json('zip_codes')->nullable(); // Lista específica de CEPs
            $table->json('conditions')->nullable(); // Condições específicas da zona
            $table->boolean('is_active')->default(true); // Status ativo
            $table->integer('sort_order')->default(0); // Ordem de exibição
            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index('code');
            $table->index('type');
            $table->index('country_code');
            $table->index('state_code');
            $table->index('is_active');
            $table->index('sort_order');
        });
    }

    /**
     * Reverte as migrações
     */
    public function down(): void
    {
        Schema::dropIfExists('shipping_zones');
    }
};