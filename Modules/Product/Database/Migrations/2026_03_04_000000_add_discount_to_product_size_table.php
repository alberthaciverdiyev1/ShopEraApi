<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_size', function (Blueprint $table) {
            $table->decimal('discount', 10, 2)->nullable()->after('price');
        });
    }
};
