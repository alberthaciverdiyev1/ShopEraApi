<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Marketing copy shown inside the promote modals (one paragraph), translatable
 * JSON, edited from the admin promotions panel.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('promotion_packages', function (Blueprint $table) {
            if (! Schema::hasColumn('promotion_packages', 'description')) {
                $table->json('description')->nullable()->after('name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('promotion_packages', function (Blueprint $table) {
            $table->dropColumn('description');
        });
    }
};
