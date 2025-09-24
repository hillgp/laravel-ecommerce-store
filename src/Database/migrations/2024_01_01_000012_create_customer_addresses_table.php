<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('customer_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->string('type')->default('delivery'); // delivery, billing
            $table->string('postal_code', 9); // CEP formatado
            $table->string('street');
            $table->string('number');
            $table->string('complement')->nullable();
            $table->string('neighborhood');
            $table->string('city');
            $table->string('state', 2); // UF
            $table->string('country')->default('BR');
            $table->string('recipient_name')->nullable();
            $table->string('recipient_phone')->nullable();
            $table->boolean('is_default')->default(false);
            $table->json('correios_data')->nullable(); // Dados da consulta de CEP
            $table->timestamps();

            $table->index(['customer_id', 'type']);
            $table->index('postal_code');
            $table->index('is_default');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_addresses');
    }
};