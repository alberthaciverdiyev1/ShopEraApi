<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('control')->create('theme_colors', function (Blueprint $table) {
            $table->id();
            // NULL = platform default palette; otherwise owner-specific override.
            $table->foreignId('site_owner_id')->nullable()->constrained('site_owners')->cascadeOnDelete();
            $table->string('key', 64);
            $table->string('value', 255);
            $table->string('label')->nullable();
            $table->timestamps();
            $table->index(['site_owner_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::connection('control')->dropIfExists('theme_colors');
    }
};
