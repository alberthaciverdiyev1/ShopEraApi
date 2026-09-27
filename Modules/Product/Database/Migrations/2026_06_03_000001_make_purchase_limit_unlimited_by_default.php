<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('products', 'purchase_limit')) {
            return;
        }

        DB::table('products')
            ->where('purchase_limit', 100)
            ->update(['purchase_limit' => null]);

        DB::statement('ALTER TABLE products ALTER COLUMN purchase_limit DROP DEFAULT');
    }

    public function down(): void
    {
        if (!Schema::hasColumn('products', 'purchase_limit')) {
            return;
        }

        DB::statement('ALTER TABLE products ALTER COLUMN purchase_limit SET DEFAULT 100');
    }
};
