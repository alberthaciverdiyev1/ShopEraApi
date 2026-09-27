<?php

namespace Modules\Live\Support;

/**
 * Pulls the video id out of whatever the admin pasted.
 *
 * YouTube hands out a different URL shape depending on where you copied it
 * from — the watch page, the share button, the Studio live dashboard, a
 * Short. Asking someone mid-broadcast to notice which one they have, and to
 * trim it down to eleven characters, is asking for a mistake at the worst
 * possible moment. So every shape is accepted, and a bare id too.
 */
class YouTubeVideoId
{
    private const ID = '[A-Za-z0-9_-]{11}';

    public static function parse(?string $input): ?string
    {
        $input = trim((string) $input);

        if ($input === '') {
            return null;
        }

        // Already just the id.
        if (preg_match('~^' . self::ID . '$~', $input) === 1) {
            return $input;
        }

        $patterns = [
            '~youtu\.be/(' . self::ID . ')~',              // share link
            '~[?&]v=(' . self::ID . ')~',                  // watch?v=
            '~/live/(' . self::ID . ')~',                  // channel live page
            '~/shorts/(' . self::ID . ')~',                // vertical
            '~/embed/(' . self::ID . ')~',                 // embed
            '~/v/(' . self::ID . ')~',                     // very old form
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $input, $matches) === 1) {
                return $matches[1];
            }
        }

        return null;
    }
}
