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
        Schema::create('order_status_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id'); // Pedido
            $table->string('status'); // Status do pedido
            $table->string('previous_status')->nullable(); // Status anterior
            $table->unsignedBigInteger('user_id')->nullable(); // Usuário que fez a alteração
            $table->text('notes')->nullable(); // Observações da alteração
            $table->json('meta_data')->nullable(); // Metadados adicionais
            $table->timestamps();

            // Índices
            $table->index(['order_id', 'status']);
            $table->index('user_id');

            // Foreign keys
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverte as migrações
     */
    public function down(): void
    {
        Schema::dropIfExists('order_status_history');
    }
};