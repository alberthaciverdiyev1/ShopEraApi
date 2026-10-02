<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('banners', function (Blueprint $table) {
            if (! Schema::hasColumn('banners', 'product_id')) {
                $table->foreignId('product_id')
                    ->nullable()
                    ->after('url')
                    ->constrained('products')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('banners', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('product_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('banners', function (Blueprint $table) {
            if (Schema::hasColumn('banners', 'product_id')) {
                $table->dropConstrainedForeignId('product_id');
            }

            if (Schema::hasColumn('banners', 'is_active')) {
                $table->dropColumn('is_active');
            }
        });
    }
};
