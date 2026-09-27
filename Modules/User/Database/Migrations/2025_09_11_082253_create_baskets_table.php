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
        Schema::create('baskets',static function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');

            $table->integer('quantity')->default(1);

            $table->foreignId('color_id')->nullable()->constrained('colors');
            $table->foreignId('size_id')->nullable()->constrained('sizes');
            $table->binary('gender')->nullable();

            $table->boolean('selected')->default(true);

            $table->boolean('is_ordered')->default(false);
            $table->string('transaction_id')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('baskets');
    }
};
