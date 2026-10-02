<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('control')->table('theme_colors', function (Blueprint $table) {
            // Palette rows belong either to a theme (theme_id) or to an owner
            // override (site_owner_id). Both nullable.
            $table->foreignId('theme_id')->nullable()->after('site_owner_id')->constrained('themes')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::connection('control')->table('theme_colors', function (Blueprint $table) {
            $table->dropConstrainedForeignId('theme_id');
        });
    }
};
