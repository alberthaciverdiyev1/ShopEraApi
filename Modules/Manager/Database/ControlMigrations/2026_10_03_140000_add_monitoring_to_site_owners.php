<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('control')->table('site_owners', function (Blueprint $table) {
            if (! Schema::connection('control')->hasColumn('site_owners', 'last_login_at')) {
                $table->timestamp('last_login_at')->nullable();
            }
            if (! Schema::connection('control')->hasColumn('site_owners', 'last_logout_at')) {
                $table->timestamp('last_logout_at')->nullable()->after('last_login_at');
            }
            if (! Schema::connection('control')->hasColumn('site_owners', 'last_seen_at')) {
                $table->timestamp('last_seen_at')->nullable()->after('last_logout_at');
            }
            if (! Schema::connection('control')->hasColumn('site_owners', 'last_action')) {
                $table->string('last_action')->nullable()->after('last_seen_at');
            }
            if (! Schema::connection('control')->hasColumn('site_owners', 'usage_orders')) {
                $table->unsignedInteger('usage_orders')->default(0);
            }
            if (! Schema::connection('control')->hasColumn('site_owners', 'usage_customers')) {
                $table->unsignedInteger('usage_customers')->default(0)->after('usage_orders');
            }
            if (! Schema::connection('control')->hasColumn('site_owners', 'usage_db_mb')) {
                $table->decimal('usage_db_mb', 12, 2)->default(0)->after('usage_customers');
            }
        });
    }

    public function down(): void
    {
        Schema::connection('control')->table('site_owners', function (Blueprint $table) {
            $table->dropColumn(['last_login_at', 'last_logout_at', 'last_seen_at', 'last_action', 'usage_orders', 'usage_customers', 'usage_db_mb']);
        });
    }
};
