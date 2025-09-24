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
        Schema::create('notification_settings', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // Tipo da configuração (email, sms, push, etc)
            $table->string('key')->unique(); // Chave da configuração
            $table->text('value')->nullable(); // Valor da configuração
            $table->string('group')->default('general'); // Grupo da configuração
            $table->text('description')->nullable(); // Descrição da configuração
            $table->string('input_type')->default('text'); // Tipo do input (text, textarea, select, boolean, etc)
            $table->json('options')->nullable(); // Opções para select/radio
            $table->boolean('is_public')->default(false); // Pode ser alterada pelo usuário
            $table->integer('sort_order')->default(0); // Ordem de exibição
            $table->timestamps();

            // Índices
            $table->index(['type', 'group']);
            $table->index('key');
            $table->index('is_public');
            $table->index('sort_order');
        });
    }

    /**
     * Reverte as migrações
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_settings');
    }
};