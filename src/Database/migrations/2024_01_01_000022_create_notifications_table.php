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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // Tipo da notificação (email, sms, push, etc)
            $table->string('channel'); // Canal (mail, sms, database, etc)
            $table->string('recipient_type'); // Tipo do destinatário (customer, admin, system)
            $table->unsignedBigInteger('recipient_id'); // ID do destinatário
            $table->string('title'); // Título da notificação
            $table->text('message'); // Mensagem da notificação
            $table->json('data')->nullable(); // Dados adicionais
            $table->timestamp('scheduled_at')->nullable(); // Agendada para
            $table->timestamp('sent_at')->nullable(); // Enviada em
            $table->string('status')->default('pending'); // Status (pending, sent, failed, cancelled)
            $table->string('provider')->nullable(); // Provedor usado (mailgun, twilio, etc)
            $table->string('external_id')->nullable(); // ID externo do provedor
            $table->text('response')->nullable(); // Resposta do provedor
            $table->integer('retry_count')->default(0); // Tentativas de envio
            $table->timestamp('last_attempt_at')->nullable(); // Última tentativa
            $table->text('error_message')->nullable(); // Mensagem de erro
            $table->json('metadata')->nullable(); // Metadados adicionais
            $table->timestamps();

            // Índices
            $table->index(['type', 'channel']);
            $table->index(['recipient_type', 'recipient_id']);
            $table->index('status');
            $table->index('scheduled_at');
            $table->index('sent_at');
            $table->index('provider');
        });
    }

    /**
     * Reverte as migrações
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};