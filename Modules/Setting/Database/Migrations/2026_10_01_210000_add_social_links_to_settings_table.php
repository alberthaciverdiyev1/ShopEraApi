<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->string('facebook_url')->nullable()->after('instagram_url');
            $table->string('twitter_url')->nullable()->after('facebook_url');
            $table->string('youtube_url')->nullable()->after('twitter_url');
            $table->string('telegram_url')->nullable()->after('youtube_url');
            $table->string('linkedin_url')->nullable()->after('telegram_url');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn([
                'facebook_url',
                'twitter_url',
                'youtube_url',
                'telegram_url',
                'linkedin_url',
            ]);
        });
    }
};
