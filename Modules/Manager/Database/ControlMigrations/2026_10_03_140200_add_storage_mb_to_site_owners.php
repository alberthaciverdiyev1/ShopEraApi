<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('control')->table('site_owners', function (Blueprint $table) {
            if (! Schema::connection('control')->hasColumn('site_owners', 'usage_storage_mb')) {
                $table->decimal('usage_storage_mb', 12, 2)->default(0);
            }
        });
    }

    public function down(): void
    {
        Schema::connection('control')->table('site_owners', function (Blueprint $table) {
            $table->dropColumn('usage_storage_mb');
        });
    }
};
