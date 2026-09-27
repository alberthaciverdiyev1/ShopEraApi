<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_videos', function (Blueprint $table) {
            $table->timestamp('story_expires_at')->nullable()->after('video_path');
            $table->boolean('is_story_hidden')->default(false)->after('story_expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('product_videos', function (Blueprint $table) {
            $table->dropColumn(['story_expires_at', 'is_story_hidden']);
        });
    }
};
