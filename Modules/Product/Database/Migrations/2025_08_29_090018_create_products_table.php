<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Brand\Entities\Brand;
use Modules\Category\Entities\Category;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            if (Module::find('Brand')->isEnabled()) {
                $table->foreignIdFor(Brand::class)
                    ->nullable()
                    ->constrained()
                    ->cascadeOnUpdate()
                    ->nullOnDelete();
            }

            if (Module::find('Category')->isEnabled()) {
                $table->foreignIdFor(Category::class)
                    ->nullable()
                    ->constrained()
                    ->cascadeOnUpdate()
                    ->nullOnDelete();
            }

            $table->json('title')->nullable();
            $table->json('description')->nullable();
            $table->string('gender')->nullable();
            $table->string('sku')->nullable();
            $table->decimal('price', 10, 2)->index();
            $table->boolean('is_active')->default(true);
            $table->decimal('discount', 10, 2)->nullable()->index();
            $table->integer('stock_count')->default(0)->index();
            $table->integer('views')->default(0)->index();
            $table->integer('sales_count')->default(0)->index();
            $table->foreignId('user_id')->constrained()->nullOnDelete();
            $table->boolean('is_suggest')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
