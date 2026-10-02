<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Removes fields that no longer belong to the product: mobile app versions,
 * the marketplace/store-seller settings and the seller instructions.
 */
return new class extends Migration
{
    private array $columns = [
        'app_version',
        'app_version_ios',
        'seller_instructions',
        'store_commission_percent',
        'store_negative_balance_limit',
        'store_handover_hours',
        'store_late_penalty_amount',
    ];

    public function up(): void
    {
        $drop = array_values(array_filter(
            $this->columns,
            fn (string $column) => Schema::hasColumn('settings', $column)
        ));

        if ($drop === []) {
            return;
        }

        Schema::table('settings', function (Blueprint $table) use ($drop) {
            $table->dropColumn($drop);
        });
    }

    public function down(): void
    {
        // Recreated shape if ever needed (values are not restored).
        Schema::table('settings', function (Blueprint $table) {
            $table->string('app_version', 20)->default('1.0.0');
            $table->string('app_version_ios', 20)->default('1.0.0');
            $table->json('seller_instructions')->nullable();
            $table->decimal('store_commission_percent', 5, 2)->default(10);
            $table->decimal('store_negative_balance_limit', 12, 2)->default(10);
            $table->unsignedSmallInteger('store_handover_hours')->default(24);
            $table->decimal('store_late_penalty_amount', 12, 2)->default(0);
        });
    }
};
