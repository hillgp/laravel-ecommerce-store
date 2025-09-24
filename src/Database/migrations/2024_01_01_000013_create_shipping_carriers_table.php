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
        Schema::create('shipping_carriers', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Nome da transportadora
            $table->string('code')->unique(); // Código único (ex: CORREIOS, FEDEX, etc)
            $table->string('tracking_url')->nullable(); // URL para rastreamento
            $table->boolean('is_active')->default(true);
            $table->json('settings')->nullable(); // Configurações específicas da transportadora
            $table->decimal('min_weight', 8, 3)->nullable(); // Peso mínimo em kg
            $table->decimal('max_weight', 8, 3)->nullable(); // Peso máximo em kg
            $table->decimal('min_value', 10, 2)->nullable(); // Valor mínimo do pedido
            $table->decimal('max_value', 10, 2)->nullable(); // Valor máximo do pedido
            $table->json('supported_regions')->nullable(); // Regiões atendidas
            $table->integer('sort_order')->default(0);
            $table->timestamps();

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
        Schema::dropIfExists('shipping_carriers');
    }
};