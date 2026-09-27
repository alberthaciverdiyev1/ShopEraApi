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
        Schema::create('delivery_prices', function (Blueprint $table) {
            $table->id();
            $table->string('city_name')->unique();
            $table->decimal('price', 8, 2);
            $table->decimal('free_from', 8, 2)->default(0);
            $table->string('delivery_time')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_prices');
    }
};
