<?php

namespace Modules\Live\Services;

use App\Jobs\SendNotificationChunkJob;
use Illuminate\Support\Facades\Log;
use Modules\Live\Http\Entities\LiveStream;
use Modules\Notification\Http\Entities\NotificationToken;
use Modules\Notification\Services\NotificationService;
use Modules\Notification\Services\SendNotificationService;

/**
 * Announces a stream to every device, reusing the broadcast path the admin
 * panel already uses so delivery, chunking and dead-token cleanup behave
 * identically.
 *
 * Backward compatibility note: the payload deliberately carries no `url`.
 * The shipped app checks `data['url']` first and, when it is not a product
 * link, hands it to an external browser — which would drag a shopper out of
 * the app. With `url` absent it falls through to `data['type']`, does not
 * recognise `live_started`, and lands on the notifications list. That is a
 * harmless destination for a build that has no live screen, and the newer
 * build routes on the same `type` to the stream.
 */
class LiveNotifier
{
    public function __construct(
        private NotificationService $notificationService,
        private NotificationToken $tokenModel,
    ) {
    }

    public function streamStarted(LiveStream $stream): void
    {
        $title = __('Canlı yayım başladı');
        $body = $stream->title;

        $data = [
            'type' => 'live_started',
            'live_stream_id' => (string) $stream->id,
        ];

        $payload = [
            'title' => $title,
            'body' => $body,
            'data' => $data,
            'source' => 'system',
        ];

        $this->notificationService->addMultiple($payload, [], true);

        $this->tokenModel
            ->where('is_active', true)
            ->whereNotNull('token')
            ->select(['id', 'token'])
            ->orderBy('id')
            ->chunk(SendNotificationService::BROADCAST_CHUNK, function ($rows) use ($title, $body, $data) {
                $tokens = $rows->pluck('token')->filter()->values()->all();

                if ($tokens === []) {
                    return;
                }

                SendNotificationChunkJob::dispatch(
                    $tokens,
                    $title,
                    $body,
                    null,
                    $data,
                    false,
                    null,
                    null
                );
            });

        Log::info('Live stream announced', ['live_stream_id' => $stream->id]);
    }
}
