<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'expires_at')) {
                $table->timestamp('expires_at')->nullable()->after('is_active')->index();
            }
        });

        Schema::table('settings', function (Blueprint $table) {
            if (! Schema::hasColumn('settings', 'listing_active_days')) {
                $table->unsignedInteger('listing_active_days')->default(30)->after('admin_disclaimer');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'expires_at')) {
                $table->dropColumn('expires_at');
            }
        });
        Schema::table('settings', function (Blueprint $table) {
            if (Schema::hasColumn('settings', 'listing_active_days')) {
                $table->dropColumn('listing_active_days');
            }
        });
    }
};
