<?php

namespace Modules\Live\Http\Requests;

use Illuminate\Validation\ValidationException;
use Modules\Live\Support\YouTubeVideoId;

/**
 * Turns whatever was pasted into the video id before validation runs.
 *
 * The admin copies the link from wherever they happen to be — the watch page,
 * the share sheet, the live dashboard — and pastes it as is. Trimming it down
 * is our job, not theirs. A link we cannot read is rejected here rather than
 * quietly dropped, because a stream saved without a video is a stream that
 * fails at the worst moment.
 */
trait ParsesYouTubeLink
{
    protected function prepareForValidation(): void
    {
        if (! $this->has('youtube_url')) {
            return;
        }

        $raw = trim((string) $this->input('youtube_url'));

        // An empty field clears the video rather than failing — that is how
        // someone corrects a stream they attached the wrong link to.
        if ($raw === '') {
            $this->merge(['youtube_video_id' => null]);

            return;
        }

        $videoId = YouTubeVideoId::parse($raw);

        if ($videoId === null) {
            throw ValidationException::withMessages([
                'youtube_url' => 'YouTube linki tanınmadı. Videonun ünvanını olduğu kimi yapışdırın.',
            ]);
        }

        $this->merge(['youtube_video_id' => $videoId]);
    }
}
