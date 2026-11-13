<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Each size keeps its own stock count next to its price. */
    public function up(): void
    {
        if (Schema::hasColumn('product_size', 'stock')) {
            return;
        }

        Schema::table('product_size', function (Blueprint $table) {
            $table->unsignedInteger('stock')->nullable()->after('wholesale_price');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('product_size', 'stock')) {
            return;
        }

        Schema::table('product_size', function (Blueprint $table) {
            $table->dropColumn('stock');
        });
    }
};
