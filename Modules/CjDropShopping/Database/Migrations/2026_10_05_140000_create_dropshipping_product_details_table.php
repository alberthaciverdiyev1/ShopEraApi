<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CJ-specific product fields that do NOT belong in the core `products` table,
 * plus the pid<->product mapping used for idempotent imports.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dropshipping_product_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->unique()->constrained('products')->cascadeOnDelete();

            $table->string('cj_product_id')->unique();
            $table->string('cj_vid')->nullable();
            $table->string('cj_sku')->nullable();
            $table->string('cj_category_id')->nullable();
            $table->string('cj_category_name')->nullable();
            $table->string('supplier_id')->nullable();
            $table->string('supplier_name')->nullable();
            $table->string('product_type')->nullable();
            $table->string('warehouse')->nullable();
            $table->string('area_country_code', 8)->nullable();
            $table->string('shop_method')->nullable();
            $table->decimal('weight_grams', 12, 2)->nullable();
            $table->decimal('pack_weight_grams', 12, 2)->nullable();
            $table->string('cj_price')->nullable();
            $table->decimal('cj_discount_price', 12, 2)->nullable();
            $table->integer('listed_num')->default(0);
            $table->string('sale_status')->nullable();
            $table->boolean('is_free_shipping')->default(false);
            $table->json('shipping_country_codes')->nullable();
            $table->json('variants')->nullable();
            $table->json('raw')->nullable();
            $table->json('raw_my')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dropshipping_product_details');
    }
};
