<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the VIP placement alongside the existing promoted/premium flags.
 * VIP and Premium share the same "VIP pool" in search (both rotate randomly);
 * Premium additionally stays on the home page until it expires.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'is_vip')) {
                $table->boolean('is_vip')->default(false)->after('premium_until')->index();
            }

            if (! Schema::hasColumn('products', 'vip_until')) {
                $table->timestamp('vip_until')->nullable()->after('is_vip');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['is_vip', 'vip_until']);
        });
    }
};
