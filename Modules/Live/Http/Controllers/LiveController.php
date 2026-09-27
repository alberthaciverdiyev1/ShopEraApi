<?php

namespace Modules\Live\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Live\Http\Entities\LiveStream;
use Modules\Live\Http\Requests\LiveChatMessageRequest;
use Modules\Live\Http\Requests\LiveEventRequest;
use Modules\Live\Http\Resources\LiveChatMessageResource;
use Modules\Live\Http\Resources\LiveStreamListResource;
use Modules\Live\Http\Resources\LiveStreamResource;
use Modules\Live\Services\LiveChatService;
use Modules\Live\Services\LiveStatsService;
use Modules\Live\Services\LiveStreamService;
use Modules\Live\Services\LiveViewerCounter;

/**
 * The shopper-facing half of live shopping.
 *
 * Watching is open to everyone, signed in or not — an account is only asked
 * for at the point of writing, liking or buying, which is where the app sends
 * the viewer to register.
 *
 * The public routes carry no auth middleware, so the viewer is read from the
 * sanctum guard directly, the way the catalogue does it.
 */
class LiveController extends Controller
{
    public function __construct(
        private LiveStreamService $streams,
        private LiveChatService $chat,
        private LiveStatsService $stats,
        private LiveViewerCounter $viewers,
    ) {
    }

    /**
     * What the app asks on startup. Returns null data rather than a 404 when
     * nothing is on air, so the caller does not have to treat "no stream" as
     * an error.
     */
    public function current()
    {
        $stream = $this->streams->current();

        return responseHelper(
            'Live stream state retrieved successfully.',
            200,
            $stream ? (new LiveStreamListResource($stream))->resolve() : null
        );
    }

    /** On-air stream plus replays still inside their three-day window. */
    public function index()
    {
        return responseHelper(
            'Live streams retrieved successfully.',
            200,
            LiveStreamListResource::collection($this->streams->visible())->resolve()
        );
    }

    public function show(Request $request, int $id)
    {
        $stream = $this->streams->findVisible($id);

        if (! $stream) {
            return responseHelper('Live stream not found.', 404);
        }

        $this->streams->withRail($stream);

        return responseHelper(
            'Live stream retrieved successfully.',
            200,
            (new LiveStreamResource($stream))->resolve($request)
        );
    }

    /** Readable by guests: the chat is part of watching. */
    public function messages(Request $request, int $id)
    {
        $stream = $this->streams->findVisible($id);

        if (! $stream) {
            return responseHelper('Live stream not found.', 404);
        }

        $messages = $this->chat->history($stream, $request->integer('after_id') ?: null);

        return responseHelper(
            'Messages retrieved successfully.',
            200,
            LiveChatMessageResource::collection($messages)->resolve()
        );
    }

    public function sendMessage(LiveChatMessageRequest $request, int $id)
    {
        $stream = $this->streams->findVisible($id);

        if (! $stream) {
            return responseHelper('Live stream not found.', 404);
        }

        $result = $this->chat->send($stream, $request->user(), $request->validated()['body']);

        if (! $result['ok']) {
            return responseHelper($result['error'], $result['status']);
        }

        return responseHelper(
            'Message sent.',
            200,
            (new LiveChatMessageResource($result['message']))->resolve()
        );
    }

    public function like(int $id)
    {
        $stream = $this->streams->findVisible($id);

        if (! $stream) {
            return responseHelper('Live stream not found.', 404);
        }

        $stream = $this->streams->like($stream);
        $this->stats->record($stream, 'like', null, auth()->id());

        return responseHelper('Liked.', 200, ['like_count' => (int) $stream->like_count]);
    }

    /**
     * Product taps and add-to-cart, which is what the sales figures on the
     * statistics screen are built from.
     */
    public function track(LiveEventRequest $request, int $id)
    {
        $stream = $this->streams->findVisible($id);

        if (! $stream) {
            return responseHelper('Live stream not found.', 404);
        }

        $validated = $request->validated();

        $this->stats->record(
            $stream,
            $validated['type'],
            (int) $validated['product_id'],
            auth('sanctum')->id(),
        );

        return responseHelper('Recorded.', 200);
    }

    /**
     * Sent about once a minute while the stream is open. Guests pass the
     * anonymous id their app generated, so they are counted without an account.
     */
    public function heartbeat(Request $request, int $id)
    {
        $stream = $this->streams->findVisible($id);

        if (! $stream) {
            return responseHelper('Live stream not found.', 404);
        }

        if ($stream->status === LiveStream::STATUS_LIVE) {
            $this->viewers->heartbeat(
                $stream,
                $this->viewers->viewerKey(auth('sanctum')->id(), $request->string('guest_id')->toString())
            );
        }

        return responseHelper('Ok.', 200, ['viewer_count' => (int) $stream->viewer_count]);
    }
}
