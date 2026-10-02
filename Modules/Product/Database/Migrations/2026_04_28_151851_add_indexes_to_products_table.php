<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

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
        // GIN / pg_trgm trigram indexes are PostgreSQL-only.
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        // pg_trgm is not guaranteed on every server (e.g. shared global-postgres);
        // skip the trigram indexes when it is unavailable.
        try {
            DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');
        } catch (Throwable) {
            return;
        }

        $locales = ['az', 'en', 'ru', 'tr'];

        foreach ($locales as $locale) {
            try {
                DB::statement("CREATE INDEX CONCURRENTLY IF NOT EXISTS products_title_{$locale}_trgm_idx ON products USING gin ((title->>'{$locale}') gin_trgm_ops)");
            } catch (Throwable) {
                // Ignore: the extension may be present without this operator class.
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        $locales = ['az', 'en', 'ru', 'tr'];

        foreach ($locales as $locale) {
            DB::statement("DROP INDEX CONCURRENTLY IF EXISTS products_title_{$locale}_trgm_idx");
        }
    }
};
