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
        Schema::create('customer_addresses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id'); // Cliente proprietário
            $table->string('type')->default('billing'); // Tipo: billing, shipping
            $table->string('name'); // Nome do destinatário
            $table->string('email')->nullable(); // Email do destinatário
            $table->string('phone')->nullable(); // Telefone do destinatário
            $table->string('company')->nullable(); // Empresa
            $table->string('document')->nullable(); // CPF/CNPJ
            $table->string('street'); // Rua/Avenida
            $table->string('number'); // Número
            $table->string('complement')->nullable(); // Complemento
            $table->string('neighborhood')->nullable(); // Bairro
            $table->string('city'); // Cidade
            $table->string('state'); // Estado
            $table->string('zipcode'); // CEP
            $table->string('country')->default('BR'); // País
            $table->text('instructions')->nullable(); // Instruções de entrega
            $table->boolean('is_default')->default(false); // Se é o endereço padrão
            $table->boolean('is_active')->default(true); // Se o endereço está ativo
            $table->json('meta_data')->nullable(); // Metadados adicionais
            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index(['customer_id', 'type']);
            $table->index(['customer_id', 'is_default']);
            $table->index('zipcode');
            $table->index('is_active');

            // Foreign key
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
        });
    }

    /**
     * Reverte as migrações
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_addresses');
    }
};