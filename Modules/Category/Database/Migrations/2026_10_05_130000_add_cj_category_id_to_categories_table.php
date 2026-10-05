<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * External CJ Dropshipping category id, so imported categories can be synced
 * idempotently and products can be linked back to their CJ category.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->string('cj_category_id')->nullable()->unique()->after('parent_id');
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropUnique(['cj_category_id']);
            $table->dropColumn('cj_category_id');
        });
    }
};
