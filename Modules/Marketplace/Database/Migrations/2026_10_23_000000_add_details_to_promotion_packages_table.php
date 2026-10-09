<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Marketing copy shown inside the promote modals (one paragraph + an optional
 * "bonus" note). Translatable JSON, edited from the admin promotions panel.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('promotion_packages', function (Blueprint $table) {
            if (! Schema::hasColumn('promotion_packages', 'description')) {
                $table->json('description')->nullable()->after('name');
            }

            if (! Schema::hasColumn('promotion_packages', 'bonus')) {
                $table->json('bonus')->nullable()->after('description');
            }
        });
    }

    public function down(): void
    {
        Schema::table('promotion_packages', function (Blueprint $table) {
            $table->dropColumn(['description', 'bonus']);
        });
    }
};
