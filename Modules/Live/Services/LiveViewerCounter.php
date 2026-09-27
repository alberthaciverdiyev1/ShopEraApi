<?php

namespace Modules\Live\Services;

use Illuminate\Support\Facades\DB;
use Modules\Live\Http\Entities\LiveStream;

/**
 * Counts who is watching — right now, and over the whole broadcast.
 *
 * Every open screen sends a heartbeat about once a minute; the badge figure is
 * recounted from those once a minute and pushed down the stream's public
 * channel, so every screen shows the same number.
 *
 * Rows are kept for the life of the stream instead of pruned. That is one row
 * per person, and it is what lets the unique-viewer statistic include guests:
 * counted from view events it would only have known signed-in users, and
 * watching without an account is the whole point.
 *
 * One upsert per viewer per minute is well within what the database shrugs off:
 * a thousand-strong audience is roughly seventeen writes a second.
 */
class LiveViewerCounter
{
    /** A viewer is considered gone this long after their last heartbeat. */
    private const STALE_AFTER_SECONDS = 90;

    public function heartbeat(LiveStream $stream, string $viewerKey): void
    {
        DB::table('live_stream_viewers')->upsert(
            [[
                'live_stream_id' => $stream->id,
                'viewer_key' => mb_substr($viewerKey, 0, 64),
                'last_seen_at' => now(),
            ]],
            ['live_stream_id', 'viewer_key'],
            ['last_seen_at'],
        );
    }

    /** Watching right now. */
    public function count(LiveStream $stream): int
    {
        return (int) DB::table('live_stream_viewers')
            ->where('live_stream_id', $stream->id)
            ->where('last_seen_at', '>', now()->subSeconds(self::STALE_AFTER_SECONDS))
            ->count();
    }

    /** Everyone who watched at any point, signed in or not. */
    public function unique(LiveStream $stream): int
    {
        return (int) DB::table('live_stream_viewers')
            ->where('live_stream_id', $stream->id)
            ->count();
    }

    /** Identifies a viewer: their account when signed in, their device otherwise. */
    public function viewerKey(?int $userId, ?string $guestId): string
    {
        if ($userId !== null) {
            return 'u:' . $userId;
        }

        return 'g:' . substr(sha1((string) ($guestId ?: request()->ip())), 0, 40);
    }
}
