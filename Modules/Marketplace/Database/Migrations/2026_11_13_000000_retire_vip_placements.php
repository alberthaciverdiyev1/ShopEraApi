<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * VIP is retired: only "irəli çək" (promoted) and premium placements remain.
     * Existing VIP packages are deactivated (order history is kept) and any
     * listing still carrying the VIP flag is cleared so nothing shows a badge.
     */
    public function up(): void
    {
        DB::table('promotion_packages')
            ->where('type', 'vip')
            ->update(['is_active' => false, 'updated_at' => now()]);

        DB::table('products')
            ->where('is_vip', true)
            ->update(['is_vip' => false, 'vip_until' => null, 'updated_at' => now()]);
    }

    public function down(): void
    {
        // VIP stays retired; a rollback must not reactivate packages.
    }
};
