<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CJ Dropshipping API key, editable from the admin settings page only when the
 * `cj_dropshipping` feature is enabled for the tenant. CJ authenticates with
 * this key alone (no account e-mail).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->text('cj_dropshipping_api_key')->nullable()->after('public_low_stock_threshold');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn('cj_dropshipping_api_key');
        });
    }
};
