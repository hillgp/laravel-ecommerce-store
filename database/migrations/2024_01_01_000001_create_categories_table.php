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
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Nome da categoria
            $table->string('slug')->unique(); // Slug para URL amigável
            $table->text('description')->nullable(); // Descrição da categoria
            $table->string('image')->nullable(); // Imagem da categoria
            $table->string('icon')->nullable(); // Ícone da categoria
            $table->unsignedBigInteger('parent_id')->nullable(); // Categoria pai (para subcategorias)
            $table->integer('sort_order')->default(0); // Ordem de exibição
            $table->boolean('is_active')->default(true); // Se a categoria está ativa
            $table->boolean('is_featured')->default(false); // Se é categoria em destaque
            $table->json('meta_data')->nullable(); // Metadados adicionais (SEO, etc)
            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index(['is_active', 'is_featured']);
            $table->index('parent_id');
            $table->index('sort_order');

            // Foreign key para categoria pai
            $table->foreign('parent_id')->references('id')->on('categories')->onDelete('set null');
        });
    }

    /**
     * Reverte as migrações
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};