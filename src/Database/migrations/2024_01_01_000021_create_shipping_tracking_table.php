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
        Schema::create('shipping_tracking', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->string('tracking_number')->nullable(); // Número de rastreio
            $table->string('carrier_name')->nullable(); // Nome da transportadora
            $table->string('carrier_code')->nullable(); // Código da transportadora
            $table->string('service_type')->nullable(); // Tipo de serviço
            $table->timestamp('shipped_at')->nullable(); // Data de envio
            $table->timestamp('delivered_at')->nullable(); // Data de entrega
            $table->timestamp('estimated_delivery')->nullable(); // Previsão de entrega
            $table->string('status')->default('pending'); // Status atual
            $table->text('notes')->nullable(); // Observações
            $table->json('tracking_history')->nullable(); // Histórico de rastreamento
            $table->string('origin_location')->nullable(); // Local de origem
            $table->string('destination_location')->nullable(); // Local de destino
            $table->decimal('weight', 10, 3)->nullable(); // Peso (kg)
            $table->decimal('length', 10, 2)->nullable(); // Comprimento (cm)
            $table->decimal('width', 10, 2)->nullable(); // Largura (cm)
            $table->decimal('height', 10, 2)->nullable(); // Altura (cm)
            $table->json('additional_info')->nullable(); // Informações adicionais
            $table->timestamps();

            // Índices
            $table->index('order_id');
            $table->index('tracking_number');
            $table->index('carrier_code');
            $table->index('status');
            $table->index('shipped_at');
            $table->index('delivered_at');
        });
    }

    /**
     * Reverte as migrações
     */
    public function down(): void
    {
        Schema::dropIfExists('shipping_tracking');
    }
};