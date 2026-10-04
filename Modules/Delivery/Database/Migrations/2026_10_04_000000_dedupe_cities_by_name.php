<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The cities table has a unique KEY but allowed two rows with the same NAME
 * (e.g. Sumgayit + Sumqayit, Khirdalan + Xirdalan). Those duplicates broke
 * keyed {#each} blocks in the storefront. Soft-delete the extras (keeping the
 * one referenced by delivery_prices, else the lowest id) and add a partial
 * unique index so it cannot happen again.
 */
return new class extends Migration
{
    public function up(): void
    {
        $names = DB::table('cities')
            ->selectRaw('lower(name) as n')
            ->whereNull('deleted_at')
            ->groupByRaw('lower(name)')
            ->havingRaw('count(*) > 1')
            ->pluck('n');

        foreach ($names as $name) {
            $rows = DB::table('cities')
                ->whereRaw('lower(name) = ?', [$name])
                ->whereNull('deleted_at')
                ->orderBy('id')
                ->get(['id', 'key']);

            if ($rows->count() < 2) {
                continue;
            }

            $usedKeys = DB::table('delivery_prices')
                ->whereIn('city_name', $rows->pluck('key'))
                ->pluck('city_name');

            $keeper = $rows->firstWhere(fn ($r) => $usedKeys->contains($r->key))->id ?? $rows->first()->id;

            DB::table('cities')
                ->whereIn('id', $rows->where('id', '!=', $keeper)->pluck('id'))
                ->update(['is_active' => false, 'deleted_at' => now()]);
        }

        DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS cities_name_unique ON cities (lower(name)) WHERE deleted_at IS NULL');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS cities_name_unique');
    }
};
