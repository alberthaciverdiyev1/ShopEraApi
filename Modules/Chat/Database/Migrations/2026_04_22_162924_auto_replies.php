<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // pg_trgm is PostgreSQL-only and not guaranteed on every server; the
        // table below is created on every driver regardless.
        if (DB::connection()->getDriverName() === 'pgsql') {
            try {
                DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');
            } catch (Throwable) {
                // Trigram search degrades to a plain ILIKE (see AutoReplyService).
            }
        }

        Schema::create('auto_replies', function (Blueprint $table) {
            $table->id();
            $table->json('question');
            $table->json('answer');
            $table->timestamps();
            $table->softDeletes();
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('auto_replies');
    }
};
