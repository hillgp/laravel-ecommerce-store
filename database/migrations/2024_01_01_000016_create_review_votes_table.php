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
        Schema::create('review_votes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('review_id'); // Avaliação votada
            $table->unsignedBigInteger('customer_id'); // Cliente que votou
            $table->boolean('is_helpful'); // Se o voto foi útil
            $table->timestamps();

            // Índices
            $table->index(['review_id', 'customer_id']);
            $table->index('customer_id');
            $table->index('is_helpful');

            // Foreign keys
            $table->foreign('review_id')->references('id')->on('product_reviews')->onDelete('cascade');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');

            // Unique constraint para evitar múltiplos votos do mesmo cliente na mesma avaliação
            $table->unique(['review_id', 'customer_id']);
        });
    }

    /**
     * Reverte as migrações
     */
    public function down(): void
    {
        Schema::dropIfExists('review_votes');
    }
};