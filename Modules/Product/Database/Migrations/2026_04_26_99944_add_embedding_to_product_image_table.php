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
            DB::statement('ALTER TABLE product_image ADD COLUMN IF NOT EXISTS embedding vector(512)');
            DB::statement('CREATE INDEX IF NOT EXISTS product_image_embedding_idx ON product_image USING hnsw (embedding vector_cosine_ops)');
        } catch (Throwable $e) {
            // pgvector unavailable on this database — skip embeddings.
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('DROP INDEX IF EXISTS product_image_embedding_idx');
        DB::statement('ALTER TABLE product_image DROP COLUMN IF EXISTS embedding');
    }
};
