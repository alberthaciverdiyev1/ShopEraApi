<?php

namespace Modules\Live\Console;

use Illuminate\Console\Command;
use Modules\Live\Http\Entities\LiveStream;
use Modules\Live\Services\LiveStreamService;
use Modules\Live\Services\LiveViewerCounter;

/**
 * Recounts the audience once a minute and pushes the new figure down the
 * socket, instead of recomputing on every heartbeat and broadcasting hundreds
 * of times a second.
 */
class RefreshLiveViewerCounts extends Command
{
    protected $signature = 'live:refresh-viewers';

    protected $description = 'Recount live viewers and broadcast the new totals';

    public function handle(LiveViewerCounter $counter, LiveStreamService $streams): int
    {
        foreach (LiveStream::query()->live()->get() as $stream) {
            $streams->updateViewerCount($stream, $counter->count($stream));
        }

        return self::SUCCESS;
    }
}
