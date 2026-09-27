<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * How the broadcast is framed, so the app knows the shape before the first
 * frame arrives.
 *
 * A phone-shot stream is 9:16 and should own the whole screen with the chat
 * floating over it; a camera-shot one is 16:9 and belongs in a band at the top
 * with the chat underneath. The player cannot tell us which it is — the iframe
 * is cross-origin and the API exposes no dimensions — so the person starting
 * the stream says it here. Existing rows stay landscape, which is what they
 * were.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('live_streams', function (Blueprint $table) {
            $table->string('orientation', 16)->default('landscape')->after('youtube_video_id');
        });
    }

    public function down(): void
    {
        Schema::table('live_streams', function (Blueprint $table) {
            $table->dropColumn('orientation');
        });
    }
};
