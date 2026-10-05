<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CJ Dropshipping credentials, editable from the admin settings page only when
 * the `cj_dropshipping` feature is enabled for the tenant.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->string('cj_dropshipping_email')->nullable()->after('public_low_stock_threshold');
            $table->text('cj_dropshipping_api_key')->nullable()->after('cj_dropshipping_email');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn(['cj_dropshipping_email', 'cj_dropshipping_api_key']);
        });
    }
};
