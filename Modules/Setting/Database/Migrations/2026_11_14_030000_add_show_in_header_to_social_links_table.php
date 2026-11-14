<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('social_links', function (Blueprint $table) {
            // Lets the admin pick which networks ride the dark header strip;
            // the footer keeps showing every active link.
            $table->boolean('show_in_header')->default(true)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('social_links', function (Blueprint $table) {
            $table->dropColumn('show_in_header');
        });
    }
};
