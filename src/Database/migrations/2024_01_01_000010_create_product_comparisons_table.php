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
        Schema::create('product_comparisons', function (Blueprint $table) {
            $table->id();
            $table->string('session_id', 40)->index();
            $table->foreignId('customer_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('name')->default('Comparação de Produtos');
            $table->integer('max_products')->default(4);
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['session_id', 'is_active']);
            $table->index(['customer_id', 'is_active']);
            $table->index(['session_id', 'created_at']);
            $table->index(['expires_at', 'is_active']);
        });

        Schema::create('product_comparison_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_comparison_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->integer('sort_order')->default(0);
            $table->text('notes')->nullable();
            $table->timestamp('added_at');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['product_comparison_id', 'product_id']);
            $table->index(['product_comparison_id', 'sort_order']);
            $table->index(['product_id', 'created_at']);
            $table->index(['product_comparison_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_comparison_items');
        Schema::dropIfExists('product_comparisons');
    }
};