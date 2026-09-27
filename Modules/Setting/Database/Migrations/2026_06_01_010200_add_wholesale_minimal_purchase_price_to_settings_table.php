<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Cache;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->decimal('wholesale_minimal_purchase_price', 12, 2)
                ->default(100)
                ->after('minimal_purchase_price');
        });

        Cache::forget('settings_list');
        Cache::forget('settings_first');
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn('wholesale_minimal_purchase_price');
        });

        Cache::forget('settings_list');
        Cache::forget('settings_first');
    }
};
