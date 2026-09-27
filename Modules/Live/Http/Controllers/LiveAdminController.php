<?php

namespace Modules\Live\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Live\Http\Entities\LiveChatMessage;
use Modules\Live\Http\Entities\LiveStream;
use Modules\Live\Http\Requests\LiveStreamStoreRequest;
use Modules\Live\Http\Requests\LiveStreamUpdateRequest;
use Modules\Live\Http\Resources\LiveChatMessageResource;
use Modules\Live\Http\Resources\LiveProductResource;
use Modules\Live\Http\Resources\LiveStreamListResource;
use Modules\Live\Http\Resources\LiveStreamResource;
use Modules\Live\Http\Resources\LiveStreamStatsResource;
use Modules\Live\Services\LiveChatService;
use Modules\Live\Services\LiveStatsService;
use Modules\Live\Services\LiveStreamService;
use Modules\Live\Support\YouTubeVideoId;

class LiveAdminController extends Controller
{
    public function __construct(
        private LiveStreamService $streams,
        private LiveChatService $chat,
        private LiveStatsService $stats,
    ) {
        // 'restrictions' must stay listed: the route group only checks the
        // token, so without this any signed-in shopper could read who is banned.
        $this->middleware('permission:view live')->only(['index', 'show', 'messages', 'statistics', 'restrictions']);
        $this->middleware('permission:manage live')->only([
            'store', 'update', 'destroy', 'addProduct', 'removeProduct',
            'setActiveProduct', 'start', 'end', 'deleteMessage', 'mute', 'block', 'lift',
        ]);
    }

    public function index(Request $request)
    {
        $streams = LiveStream::query()
            ->withCount('products')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest('id')
            ->paginate(min(max((int) $request->input('per_page', 20), 1), 100));

        return responseHelper(
            'Live streams retrieved successfully.',
            200,
            [
                'items' => LiveStreamListResource::collection($streams->items())->resolve(),
                'meta' => [
                    'current_page' => $streams->currentPage(),
                    'last_page' => $streams->lastPage(),
                    'total' => $streams->total(),
                ],
            ]
        );
    }

    public function show(Request $request, int $id)
    {
        $stream = LiveStream::find($id);

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

    public function store(LiveStreamStoreRequest $request)
    {
        $stream = $this->streams->create($request->validated(), $request->file('cover'));

        return responseHelper(
            'Live stream created successfully.',
            200,
            (new LiveStreamResource($this->streams->withRail($stream)))->resolve($request)
        );
    }

    public function update(LiveStreamUpdateRequest $request, int $id)
    {
        $stream = LiveStream::find($id);

        if (! $stream) {
            return responseHelper('Live stream not found.', 404);
        }

        $stream = $this->streams->update($stream, $request->validated(), $request->file('cover'));

        return responseHelper(
            'Live stream updated successfully.',
            200,
            (new LiveStreamResource($this->streams->withRail($stream)))->resolve($request)
        );
    }

    public function destroy(int $id)
    {
        $stream = LiveStream::find($id);

        if (! $stream) {
            return responseHelper('Live stream not found.', 404);
        }

        if ($stream->isLive()) {
            return responseHelper('End the stream before deleting it.', 409);
        }

        $stream->delete();

        return responseHelper('Live stream deleted successfully.', 200);
    }

    /** Adds a product to a rail that is already on air. */
    public function addProduct(Request $request, int $id)
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
        ]);

        $stream = LiveStream::find($id);

        if (! $stream) {
            return responseHelper('Live stream not found.', 404);
        }

        $row = $this->streams->addProduct($stream, (int) $validated['product_id']);

        return responseHelper(
            'Product added successfully.',
            200,
            (new LiveProductResource($row))->resolve($request)
        );
    }

    public function removeProduct(int $id, int $productId)
    {
        $stream = LiveStream::find($id);

        if (! $stream) {
            return responseHelper('Live stream not found.', 404);
        }

        $this->streams->removeProduct($stream, $productId);

        return responseHelper('Product removed successfully.', 200);
    }

    /** The one-tap action during a broadcast. */
    public function setActiveProduct(Request $request, int $id)
    {
        $validated = $request->validate([
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
        ]);

        $stream = LiveStream::find($id);

        if (! $stream) {
            return responseHelper('Live stream not found.', 404);
        }

        $stream = $this->streams->setActiveProduct(
            $stream,
            isset($validated['product_id']) ? (int) $validated['product_id'] : null
        );

        return responseHelper(
            'Active product updated successfully.',
            200,
            ['active_product_id' => $stream->active_product_id]
        );
    }

    /**
     * Puts a stream on air.
     *
     * The link may come with the request or already be saved on the stream —
     * whoever prepared it will usually have pasted it into the form, and then
     * this is a single confirming click. Announcing a broadcast wakes every
     * device that has the app, so it stays a deliberate press rather than a
     * side effect of saving a form.
     */
    public function start(Request $request, int $id)
    {
        $stream = LiveStream::find($id);

        if (! $stream) {
            return responseHelper('Live stream not found.', 404);
        }

        $videoId = YouTubeVideoId::parse($request->input('youtube_url'))
            ?? $stream->youtube_video_id;

        if ($videoId === null) {
            return responseHelper('YouTube linki tanınmadı. Videonun ünvanını olduğu kimi yapışdırın.', 422);
        }

        // Two streams on air at once would make "which one is current?"
        // ambiguous for the app, which asks for exactly one.
        $other = LiveStream::query()->live()->where('id', '!=', $stream->id)->first();

        if ($other) {
            return responseHelper("Əvvəlcə #{$other->id} yayımını bitirin — eyni anda yalnız bir yayım efirdə ola bilər.", 422);
        }

        $stream = $this->streams->markLive($stream, $videoId);

        return responseHelper(
            'Live stream started successfully.',
            200,
            (new LiveStreamListResource($stream))->resolve()
        );
    }

    /**
     * Takes a stream off air and opens its replay window. Nothing watches
     * YouTube for us, so this is the only thing that ends a broadcast — leave
     * it unpressed and the app keeps showing CANLI over a finished video.
     */
    public function end(int $id)
    {
        $stream = LiveStream::find($id);

        if (! $stream) {
            return responseHelper('Live stream not found.', 404);
        }

        $stream = $this->streams->markEnded($stream);

        return responseHelper(
            'Live stream ended successfully.',
            200,
            (new LiveStreamListResource($stream))->resolve()
        );
    }

    public function messages(Request $request, int $id)
    {
        $stream = LiveStream::find($id);

        if (! $stream) {
            return responseHelper('Live stream not found.', 404);
        }

        $messages = $stream->messages()
            ->orderByDesc('id')
            ->paginate(min(max((int) $request->input('per_page', 50), 1), 200));

        return responseHelper(
            'Messages retrieved successfully.',
            200,
            [
                'items' => LiveChatMessageResource::collection($messages->items())->resolve(),
                'meta' => [
                    'current_page' => $messages->currentPage(),
                    'last_page' => $messages->lastPage(),
                    'total' => $messages->total(),
                ],
            ]
        );
    }

    public function deleteMessage(int $id, int $messageId)
    {
        $message = LiveChatMessage::where('live_stream_id', $id)->find($messageId);

        if (! $message) {
            return responseHelper('Message not found.', 404);
        }

        $this->chat->remove($message, auth()->id());

        return responseHelper('Message deleted successfully.', 200);
    }

    public function mute(Request $request, int $id)
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'minutes' => ['nullable', 'integer', 'min:1', 'max:1440'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $stream = LiveStream::find($id);

        if (! $stream) {
            return responseHelper('Live stream not found.', 404);
        }

        $this->chat->mute(
            $stream,
            (int) $validated['user_id'],
            (int) ($validated['minutes'] ?? 10),
            $validated['reason'] ?? null
        );

        return responseHelper('Viewer muted successfully.', 200);
    }

    public function block(Request $request, int $id)
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'reason' => ['nullable', 'string', 'max:255'],

            // Without this the block only covers the current stream.
            'global' => ['nullable', 'boolean'],
        ]);

        $stream = LiveStream::find($id);

        if (! $stream) {
            return responseHelper('Live stream not found.', 404);
        }

        $this->chat->block(
            ($validated['global'] ?? false) ? null : $stream,
            (int) $validated['user_id'],
            $validated['reason'] ?? null
        );

        return responseHelper('Viewer blocked successfully.', 200);
    }

    public function lift(Request $request, int $id)
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $stream = LiveStream::find($id);

        if (! $stream) {
            return responseHelper('Live stream not found.', 404);
        }

        $this->chat->lift($stream, (int) $validated['user_id']);

        return responseHelper('Restriction lifted successfully.', 200);
    }

    /**
     * Who cannot write in this stream right now, one entry per viewer, so the
     * panel can mark them and offer to lift it. A block outranks a mute.
     */
    public function restrictions(int $id)
    {
        $stream = LiveStream::find($id);

        if (! $stream) {
            return responseHelper('Live stream not found.', 404);
        }

        $items = $this->chat->restrictions($stream)
            ->groupBy('user_id')
            ->map(function ($bans, $userId) {
                $block = $bans->first(fn ($ban) => $ban->is_blocked);
                $mute = $bans->reject(fn ($ban) => $ban->is_blocked)->sortByDesc('muted_until')->first();

                return [
                    'user_id' => (int) $userId,
                    'is_blocked' => $block !== null,
                    'is_global' => $block !== null && $block->live_stream_id === null,
                    'muted_until' => $block ? null : $mute?->muted_until?->toIso8601String(),
                ];
            })
            ->values()
            ->all();

        return responseHelper('Restrictions retrieved successfully.', 200, $items);
    }

    public function statistics(int $id)
    {
        $stream = LiveStream::find($id);

        if (! $stream) {
            return responseHelper('Live stream not found.', 404);
        }

        $resource = new LiveStreamStatsResource($stream, $this->stats->totals($stream));

        return responseHelper(
            'Statistics retrieved successfully.',
            200,
            $resource->resolve()
        );
    }
}
