<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hybrid taxonomy: a leaf category can require a brand (chosen from the global
 * `brands` list) and a free-text model, instead of duplicating brands/models as
 * nested categories.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            if (! Schema::hasColumn('categories', 'needs_brand')) {
                $table->boolean('needs_brand')->default(false)->after('parent_id');
            }
        });

        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'model')) {
                $table->string('model')->nullable()->after('brand_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            if (Schema::hasColumn('categories', 'needs_brand')) {
                $table->dropColumn('needs_brand');
            }
        });

        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'model')) {
                $table->dropColumn('model');
            }
        });
    }
};
