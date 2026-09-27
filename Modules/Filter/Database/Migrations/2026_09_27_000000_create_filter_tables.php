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
        Schema::create('filters', function (Blueprint $table) {
            $table->id();
            $table->json('title')->nullable();
            $table->string('type', 16)->default('select');
            $table->json('options')->nullable();
            $table->timestamps();
        });

        Schema::create('category_filters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('filter_id')->constrained('filters')->cascadeOnDelete();
            $table->unsignedBigInteger('category_id');
            $table->timestamps();

            $table->index('category_id');
        });

        Schema::create('product_filters', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->foreignId('filter_id')->constrained('filters')->cascadeOnDelete();
            $table->string('value', 255)->nullable();
            $table->timestamps();

            $table->index('product_id');
            $table->index('filter_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_filters');
        Schema::dropIfExists('category_filters');
        Schema::dropIfExists('filters');
    }
};
