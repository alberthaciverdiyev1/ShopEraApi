<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delivery_prices', function (Blueprint $table) {
            $table->unsignedBigInteger('starex_region_id')->nullable()->after('city_name');
        });

        Schema::table('pickup_points', function (Blueprint $table) {
            $table->unsignedBigInteger('starex_delivery_point_id')->nullable()->unique()->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('pickup_points', function (Blueprint $table) {
            $table->dropUnique(['starex_delivery_point_id']);
            $table->dropColumn('starex_delivery_point_id');
        });

        Schema::table('delivery_prices', function (Blueprint $table) {
            $table->dropColumn('starex_region_id');
        });
    }
};
