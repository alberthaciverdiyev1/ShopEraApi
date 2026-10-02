<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('control')->table('site_owners', function (Blueprint $table) {
            $table->unsignedInteger('usage_products')->default(0);
            $table->unsignedInteger('usage_categories')->default(0)->after('usage_products');
            $table->unsignedInteger('usage_staff')->default(0)->after('usage_categories');
            $table->decimal('usage_storage_gb', 10, 2)->default(0)->after('usage_staff');
            $table->timestamp('usage_reported_at')->nullable()->after('usage_storage_gb');
        });
    }

    public function down(): void
    {
        Schema::connection('control')->table('site_owners', function (Blueprint $table) {
            $table->dropColumn(['usage_products', 'usage_categories', 'usage_staff', 'usage_storage_gb', 'usage_reported_at']);
        });
    }
};
