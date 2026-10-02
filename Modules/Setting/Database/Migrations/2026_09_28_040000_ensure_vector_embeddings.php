<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Keeps the semantic-search columns in sync with databases that have pgvector.
 * On a PostgreSQL server without the extension (e.g. the shared global-postgres)
 * this is a no-op: AutoReplyService falls back to pg_trgm and product image
 * embeddings are simply not generated.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        try {
            DB::statement('CREATE EXTENSION IF NOT EXISTS vector');
        } catch (Throwable) {
            return;
        }

        $this->addVectorColumn('auto_replies', 'embedding', 384);
        $this->addVectorColumn('product_image', 'embedding', 512);
    }

    public function down(): void
    {
        // Left in place; dropping an embedding column would lose search data.
    }

    private function addVectorColumn(string $table, string $column, int $dimensions): void
    {
        try {
            $exists = DB::selectOne(
                "select 1 from information_schema.columns where table_schema = 'public' and table_name = ? and column_name = ?",
                [$table, $column]
            );

            if (! $exists) {
                DB::statement("ALTER TABLE \"{$table}\" ADD COLUMN \"{$column}\" vector({$dimensions})");
            }
        } catch (Throwable) {
            // The extension is present but the column type is unavailable.
        }
    }
};
