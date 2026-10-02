<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Explicit media type for the home popup, so the admin says whether it is an
 * image or a video instead of the storefront guessing.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('popups', function (Blueprint $table) {
            $table->string('type', 16)->default('image')->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('popups', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
