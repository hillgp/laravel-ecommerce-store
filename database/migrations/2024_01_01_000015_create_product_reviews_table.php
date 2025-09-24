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
        Schema::create('product_reviews', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id'); // Produto avaliado
            $table->unsignedBigInteger('customer_id'); // Cliente que fez a avaliação
            $table->unsignedBigInteger('order_id')->nullable(); // Pedido relacionado
            $table->integer('rating'); // Avaliação (1-5 estrelas)
            $table->text('title')->nullable(); // Título da avaliação
            $table->text('comment')->nullable(); // Comentário da avaliação
            $table->text('pros')->nullable(); // Pontos positivos
            $table->text('cons')->nullable(); // Pontos negativos
            $table->string('status')->default('pending'); // Status: pending, approved, rejected
            $table->boolean('is_verified_purchase')->default(false); // Se foi compra verificada
            $table->integer('helpful_votes')->default(0); // Votos úteis
            $table->integer('total_votes')->default(0); // Total de votos
            $table->json('images')->nullable(); // Imagens da avaliação
            $table->json('videos')->nullable(); // Vídeos da avaliação
            $table->json('meta_data')->nullable(); // Metadados adicionais
            $table->timestamp('reviewed_at')->nullable(); // Data da avaliação
            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index(['product_id', 'status']);
            $table->index(['customer_id', 'status']);
            $table->index('rating');
            $table->index('status');
            $table->index('is_verified_purchase');
            $table->index('helpful_votes');
            $table->index('reviewed_at');

            // Foreign keys
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('set null');

            // Unique constraint para evitar múltiplas avaliações do mesmo cliente para o mesmo produto
            $table->unique(['product_id', 'customer_id', 'order_id']);
        });
    }

    /**
     * Reverte as migrações
     */
    public function down(): void
    {
        Schema::dropIfExists('product_reviews');
    }
};