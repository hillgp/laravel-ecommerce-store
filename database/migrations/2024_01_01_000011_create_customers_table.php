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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Nome completo
            $table->string('email')->unique(); // Email único
            $table->string('password'); // Senha criptografada
            $table->string('phone')->nullable(); // Telefone
            $table->string('document')->nullable(); // CPF/CNPJ
            $table->date('birth_date')->nullable(); // Data de nascimento
            $table->string('gender')->nullable(); // Gênero
            $table->boolean('is_active')->default(true); // Se o cliente está ativo
            $table->boolean('email_verified')->default(false); // Se o email foi verificado
            $table->timestamp('email_verified_at')->nullable(); // Data de verificação do email
            $table->string('verification_token')->nullable(); // Token de verificação
            $table->timestamp('last_login_at')->nullable(); // Último login
            $table->string('avatar')->nullable(); // Avatar do cliente
            $table->json('preferences')->nullable(); // Preferências do cliente
            $table->json('meta_data')->nullable(); // Metadados adicionais
            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index('email');
            $table->index('phone');
            $table->index('document');
            $table->index('is_active');
            $table->index('email_verified');
            $table->index('last_login_at');
        });
    }

    /**
     * Reverte as migrações
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};