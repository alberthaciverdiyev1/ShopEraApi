<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public $withinTransaction = false;

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');

        $locales = ['az', 'en', 'ru', 'tr'];

        foreach ($locales as $locale) {
            DB::statement("CREATE INDEX CONCURRENTLY IF NOT EXISTS products_title_{$locale}_trgm_idx ON products USING gin ((title->>'{$locale}') gin_trgm_ops)");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $locales = ['az', 'en', 'ru', 'tr'];

        foreach ($locales as $locale) {
            DB::statement("DROP INDEX CONCURRENTLY IF EXISTS products_title_{$locale}_trgm_idx");
        }
    }
};
