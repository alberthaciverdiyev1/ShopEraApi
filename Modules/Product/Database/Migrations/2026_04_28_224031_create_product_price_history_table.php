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
        Schema::create('product_price_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->decimal('old_price', 15, 2);
            $table->decimal('new_price', 15, 2);
            $table->decimal('old_discount', 15, 2);
            $table->decimal('new_discount', 15, 2);
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('size_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('operation_type');
            $table->string('direction');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_price_history');
    }
};
