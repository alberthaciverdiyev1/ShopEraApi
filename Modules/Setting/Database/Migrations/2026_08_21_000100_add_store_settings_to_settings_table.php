<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->decimal('store_commission_percent', 5, 2)->default(10);
            $table->decimal('store_negative_balance_limit', 12, 2)->default(10);
            $table->unsignedSmallInteger('store_handover_hours')->default(24);
            $table->decimal('store_late_penalty_amount', 12, 2)->default(0);
            $table->json('seller_instructions')->nullable();
            $table->unsignedSmallInteger('public_low_stock_threshold')->default(20);
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn([
                'store_commission_percent',
                'store_negative_balance_limit',
                'store_handover_hours',
                'store_late_penalty_amount',
                'seller_instructions',
                'public_low_stock_threshold',
            ]);
        });
    }
};
