<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('banners', function (Blueprint $table) {
            if (! Schema::hasColumn('banners', 'title')) {
                $table->jsonb('title')->nullable()->after('url');
            }

            if (! Schema::hasColumn('banners', 'subtitle')) {
                $table->jsonb('subtitle')->nullable()->after('title');
            }
        });
    }

    public function down(): void
    {
        Schema::table('banners', function (Blueprint $table) {
            if (Schema::hasColumn('banners', 'subtitle')) {
                $table->dropColumn('subtitle');
            }

            if (Schema::hasColumn('banners', 'title')) {
                $table->dropColumn('title');
            }
        });
    }
};
