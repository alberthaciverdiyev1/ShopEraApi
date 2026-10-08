<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-category listing form schema: which fields appear and whether they are
 * required (tap.az-style). Missing rows fall back to ListingFields::DEFAULTS.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('category_fields', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('category_id');
            $table->string('field', 32);
            $table->boolean('is_visible')->default(true);
            $table->boolean('is_required')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['category_id', 'field']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_fields');
    }
};
