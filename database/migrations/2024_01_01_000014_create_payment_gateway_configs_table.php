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
        Schema::create('payment_gateway_configs', function (Blueprint $table) {
            $table->id();
            $table->string('gateway'); // Nome do gateway (stripe, mercadopago, pagseguro)
            $table->string('environment')->default('sandbox'); // Ambiente: sandbox, production
            $table->string('key')->unique(); // Chave da configuração
            $table->text('value')->nullable(); // Valor da configuração
            $table->string('type')->default('string'); // Tipo: string, boolean, number, json
            $table->text('description')->nullable(); // Descrição da configuração
            $table->boolean('is_public')->default(false); // Se é configuração pública
            $table->boolean('is_required')->default(false); // Se é obrigatória
            $table->json('validation_rules')->nullable(); // Regras de validação
            $table->json('meta_data')->nullable(); // Metadados adicionais
            $table->timestamps();

            // Índices
            $table->index(['gateway', 'environment']);
            $table->index(['gateway', 'key']);
            $table->index('environment');
            $table->index('is_public');
            $table->index('is_required');
        });
    }

    /**
     * Reverte as migrações
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_gateway_configs');
    }
};