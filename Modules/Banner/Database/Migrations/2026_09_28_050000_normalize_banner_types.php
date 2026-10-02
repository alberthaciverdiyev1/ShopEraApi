<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The storefront only understands "big" (hero), "middle" (best-seller) and
 * "small" (promo). Older rows used home/favorite/basket, which the frontend
 * never requested — so those banners were invisible. Normalise them.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('banners')->where('type', 'home')->update(['type' => 'big']);
        DB::table('banners')->where('type', 'favorite')->update(['type' => 'middle']);
        DB::table('banners')->where('type', 'basket')->update(['type' => 'small']);
    }

    public function down(): void
    {
        // Types are normalised forward; nothing to revert.
    }
};
