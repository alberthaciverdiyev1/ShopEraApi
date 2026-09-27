<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE auto_replies ADD COLUMN IF NOT EXISTS embedding vector(384)");

        DB::statement("
            CREATE INDEX IF NOT EXISTS auto_replies_embedding_idx
            ON auto_replies
            USING hnsw (embedding vector_cosine_ops)
        ");
    }

    public function down(): void
    {
        DB::statement("DROP INDEX IF EXISTS auto_replies_embedding_idx");
        DB::statement("ALTER TABLE auto_replies DROP COLUMN IF EXISTS embedding");
    }
};
