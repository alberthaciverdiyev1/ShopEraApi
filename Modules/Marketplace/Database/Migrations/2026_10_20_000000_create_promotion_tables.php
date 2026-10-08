<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Paid placements: packages (promoted / premium) and the per-listing orders
 * that apply them for a number of days.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotion_packages', function (Blueprint $table) {
            $table->id();
            $table->json('name')->nullable();
            $table->string('type', 20)->default('promoted'); // promoted | premium
            $table->unsignedInteger('days')->default(7);
            $table->decimal('price', 10, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('listing_promotions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->foreignId('package_id')->nullable()->constrained('promotion_packages')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 20)->default('promoted');
            $table->decimal('price', 10, 2)->default(0);
            $table->string('status', 20)->default('pending')->index(); // pending | paid | cancelled
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();

            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listing_promotions');
        Schema::dropIfExists('promotion_packages');
    }
};
