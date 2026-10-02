<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The palette stores values such as "0px 18px 45px 0px rgba(7, 17, 31, 0.08)"
 * which do not fit in the original varchar(32) column, so seeding/updating the
 * palette from the admin panel (or GET /api/theme) failed with a truncation
 * error on PostgreSQL.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('theme_colors', function (Blueprint $table) {
            $table->string('value', 255)->change();
        });
    }

    public function down(): void
    {
        Schema::table('theme_colors', function (Blueprint $table) {
            $table->string('value', 32)->change();
        });
    }
};
