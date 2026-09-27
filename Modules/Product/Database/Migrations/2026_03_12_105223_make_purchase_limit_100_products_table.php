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
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'purchase_limit')) {
                $table->integer('purchase_limit')
                    ->nullable()
                    ->default(100)
                    ->after('discount_price');
            }
        });

        DB::table('products')->whereNull('purchase_limit')->update(['purchase_limit' => 100]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'purchase_limit')) {
                $table->dropColumn('purchase_limit');
            }
        });
    }
};
