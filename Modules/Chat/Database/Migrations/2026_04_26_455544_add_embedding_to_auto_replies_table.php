<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        try {
            DB::statement('ALTER TABLE auto_replies ADD COLUMN IF NOT EXISTS embedding vector(384)');

            DB::statement('
                CREATE INDEX IF NOT EXISTS auto_replies_embedding_idx
                ON auto_replies
                USING hnsw (embedding vector_cosine_ops)
            ');
        } catch (Throwable $e) {
            // pgvector unavailable on this database — skip embeddings.
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('DROP INDEX IF EXISTS auto_replies_embedding_idx');
        DB::statement('ALTER TABLE auto_replies DROP COLUMN IF EXISTS embedding');
    }
};
