<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('control')->table('subscriptions', function (Blueprint $table) {
            $table->timestamp('last_reminder_at')->nullable()->after('cancelled_at');
            $table->unsignedInteger('reminder_count')->default(0)->after('last_reminder_at');
        });
    }

    public function down(): void
    {
        Schema::connection('control')->table('subscriptions', function (Blueprint $table) {
            $table->dropColumn(['last_reminder_at', 'reminder_count']);
        });
    }
};
