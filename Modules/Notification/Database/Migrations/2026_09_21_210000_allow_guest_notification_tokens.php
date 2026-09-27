<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A phone that has not signed in still has a device token, and the client
 * wants announcements to reach those phones too. The column was NOT NULL, so
 * a guest's token could not even be written down.
 *
 * Existing rows keep their owner; nothing else changes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notification_tokens', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Guest rows would break the NOT NULL, so they go first.
        Schema::table('notification_tokens', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable(false)->change();
        });
    }
};
