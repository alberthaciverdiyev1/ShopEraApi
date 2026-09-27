<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('social_accounts', function (Blueprint $table) {
            // Apple's refresh token, kept (encrypted) only so the grant can be
            // revoked when the account is deleted, as Apple requires.
            $table->text('refresh_token')->nullable()->after('provider_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('social_accounts', function (Blueprint $table) {
            $table->dropColumn('refresh_token');
        });
    }
};
