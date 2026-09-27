<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('live_streams', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('cover_path')->nullable();

            // Filled in by the YouTube sync once the channel actually goes live,
            // so the admin never pastes a link by hand.
            $table->string('youtube_video_id', 32)->nullable()->index();

            $table->string('status', 16)->default('draft')->index();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();

            // The replay stays in the app for three days after the stream ends.
            // Queries filter on this instead of relying on a delete job, so a
            // failed job can never leave a replay visible past its window.
            $table->timestamp('replay_until')->nullable()->index();

            $table->foreignId('active_product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->unsignedInteger('viewer_count')->default(0);
            $table->unsignedInteger('viewer_peak')->default(0);
            $table->unsignedInteger('like_count')->default(0);

            // Set the moment the "we are live" push goes out, so a sync that
            // runs every minute cannot notify the same stream twice.
            $table->timestamp('notified_at')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('live_stream_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('live_stream_id')->constrained('live_streams')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(false);

            // Seconds from the start of the stream at which this product was
            // first featured. The replay uses it to pin a product to the moment
            // it appeared in the video.
            $table->unsignedInteger('shown_at_offset')->nullable();
            $table->timestamp('shown_at')->nullable();
            $table->timestamps();

            $table->unique(['live_stream_id', 'product_id']);
            $table->index(['live_stream_id', 'sort_order']);
        });

        Schema::create('live_chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('live_stream_id')->constrained('live_streams')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            // Snapshot of the display name, so removing a user does not blank
            // out the history an admin may still need to review.
            $table->string('author_name');
            $table->boolean('is_admin')->default(false);
            $table->text('body');
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['live_stream_id', 'id']);
        });

        Schema::create('live_chat_bans', function (Blueprint $table) {
            $table->id();

            // Null scopes the ban to every stream rather than a single one.
            $table->foreignId('live_stream_id')->nullable()->constrained('live_streams')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('muted_until')->nullable();
            $table->boolean('is_blocked')->default(false);
            $table->string('reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['live_stream_id', 'user_id']);
        });

        Schema::create('live_stream_viewers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('live_stream_id')->constrained('live_streams')->cascadeOnDelete();

            // A signed-in viewer is keyed by user id, a guest by the anonymous
            // id their app generates once and keeps. Guests are counted too:
            // watching never requires an account.
            $table->string('viewer_key', 64);
            $table->timestamp('last_seen_at')->index();

            $table->unique(['live_stream_id', 'viewer_key']);
        });

        Schema::create('live_stream_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('live_stream_id')->constrained('live_streams')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 32)->index();

            // Only set on 'order' rows: the value attributed to the stream.
            $table->decimal('amount', 12, 2)->nullable();
            $table->timestamp('created_at')->nullable()->index();

            $table->index(['live_stream_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('live_stream_viewers');
        Schema::dropIfExists('live_stream_events');
        Schema::dropIfExists('live_chat_bans');
        Schema::dropIfExists('live_chat_messages');
        Schema::dropIfExists('live_stream_products');
        Schema::dropIfExists('live_streams');
    }
};
