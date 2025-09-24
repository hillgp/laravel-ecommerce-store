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
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Nome do template
            $table->string('code')->unique(); // Código único do template
            $table->string('type'); // Tipo (email, sms, push, etc)
            $table->string('channel'); // Canal (mail, sms, database, etc)
            $table->string('subject')->nullable(); // Assunto (para email)
            $table->text('content'); // Conteúdo do template
            $table->text('html_content')->nullable(); // Conteúdo HTML (para email)
            $table->json('variables')->nullable(); // Variáveis disponíveis
            $table->string('locale')->default('pt-BR'); // Idioma
            $table->boolean('is_active')->default(true); // Status ativo
            $table->json('configuration')->nullable(); // Configurações específicas
            $table->text('description')->nullable(); // Descrição do template
            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index(['type', 'channel']);
            $table->index('code');
            $table->index('locale');
            $table->index('is_active');
        });
    }

    /**
     * Reverte as migrações
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_templates');
    }
};