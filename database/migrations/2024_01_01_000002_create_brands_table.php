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
        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Nome da marca
            $table->string('slug')->unique(); // Slug para URL amigável
            $table->text('description')->nullable(); // Descrição da marca
            $table->string('logo')->nullable(); // Logo da marca
            $table->string('website')->nullable(); // Website da marca
            $table->string('email')->nullable(); // Email de contato da marca
            $table->string('phone')->nullable(); // Telefone da marca
            $table->boolean('is_active')->default(true); // Se a marca está ativa
            $table->boolean('is_featured')->default(false); // Se é marca em destaque
            $table->integer('sort_order')->default(0); // Ordem de exibição
            $table->json('meta_data')->nullable(); // Metadados adicionais (SEO, etc)
            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index(['is_active', 'is_featured']);
            $table->index('sort_order');
        });
    }

    /**
     * Reverte as migrações
     */
    public function down(): void
    {
        Schema::dropIfExists('brands');
    }
};