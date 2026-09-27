<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Modules\Live\Http\Entities\LiveStream;

/**
 * Where a shared stream link lands.
 *
 * On a phone with the app installed, the operating system opens the app before
 * this ever runs — the route exists so the same link still means something for
 * everyone else. Rather than a dead end, it hands them the broadcast on
 * YouTube, which is public anyway.
 */
class LiveShareController extends Controller
{
    public function show(int $id)
    {
        $stream = LiveStream::query()->visible()->find($id);

        if (! $stream || ! $stream->youtube_video_id) {
            return redirect()->route('storefront.home');
        }

        return redirect()->away('https://www.youtube.com/watch?v=' . $stream->youtube_video_id);
    }
}
