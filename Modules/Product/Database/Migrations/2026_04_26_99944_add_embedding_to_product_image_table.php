<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE product_image ADD COLUMN IF NOT EXISTS embedding vector(512)');
        DB::statement('CREATE INDEX IF NOT EXISTS product_image_embedding_idx ON product_image USING hnsw (embedding vector_cosine_ops)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS product_image_embedding_idx');
        DB::statement('ALTER TABLE product_image DROP COLUMN IF EXISTS embedding');
    }
};
