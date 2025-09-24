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
        // Add foreign keys to products table
        Schema::table('products', function (Blueprint $table) {
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('set null');
            $table->foreign('brand_id')->references('id')->on('brands')->onDelete('set null');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });

        // Add foreign keys to categories table
        Schema::table('categories', function (Blueprint $table) {
            $table->foreign('parent_id')->references('id')->on('categories')->onDelete('cascade');
        });

        // Add foreign keys to carts table
        Schema::table('carts', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        // Add foreign keys to orders table
        Schema::table('orders', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });

        // Add foreign keys to product_reviews table
        Schema::table('product_reviews', function (Blueprint $table) {
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('set null');
        });

        // Add foreign keys to wishlist_items table
        Schema::table('wishlist_items', function (Blueprint $table) {
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
        });

        // Add foreign keys to product_images table
        Schema::table('product_images', function (Blueprint $table) {
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
        });

        // Add foreign keys to product_categories table
        Schema::table('product_categories', function (Blueprint $table) {
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
        });

        // Add foreign keys to related_products table
        Schema::table('related_products', function (Blueprint $table) {
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('related_product_id')->references('id')->on('products')->onDelete('cascade');
        });

        // Add foreign keys to cross_sell_products table
        Schema::table('cross_sell_products', function (Blueprint $table) {
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('cross_sell_product_id')->references('id')->on('products')->onDelete('cascade');
        });

        // Add foreign keys to up_sell_products table
        Schema::table('up_sell_products', function (Blueprint $table) {
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('up_sell_product_id')->references('id')->on('products')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop foreign keys from products table
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropForeign(['brand_id']);
            $table->dropForeign(['user_id']);
        });

        // Drop foreign keys from categories table
        Schema::table('categories', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
        });

        // Drop foreign keys from carts table
        Schema::table('carts', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        // Drop foreign keys from orders table
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        // Drop foreign keys from product_reviews table
        Schema::table('product_reviews', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropForeign(['customer_id']);
            $table->dropForeign(['order_id']);
        });

        // Drop foreign keys from wishlist_items table
        Schema::table('wishlist_items', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->dropForeign(['product_id']);
        });

        // Drop foreign keys from product_images table
        Schema::table('product_images', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
        });

        // Drop foreign keys from product_categories table
        Schema::table('product_categories', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropForeign(['category_id']);
        });

        // Drop foreign keys from related_products table
        Schema::table('related_products', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropForeign(['related_product_id']);
        });

        // Drop foreign keys from cross_sell_products table
        Schema::table('cross_sell_products', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropForeign(['cross_sell_product_id']);
        });

        // Drop foreign keys from up_sell_products table
        Schema::table('up_sell_products', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropForeign(['up_sell_product_id']);
        });
    }
};