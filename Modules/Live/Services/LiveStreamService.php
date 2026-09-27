<?php

namespace Modules\Live\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Modules\Live\Events\LiveActiveProductChanged;
use Modules\Live\Events\LiveStreamStatusChanged;
use Modules\Live\Events\LiveViewerCountUpdated;
use Modules\Live\Http\Entities\LiveStream;
use Modules\Live\Http\Entities\LiveStreamProduct;
use Modules\Live\Support\CatalogueProducts;
use Modules\Live\Support\LiveBroadcast;
use Modules\Product\Http\Entities\Product;

class LiveStreamService
{
    public function __construct(private LiveNotifier $notifier)
    {
    }

    /** The stream a shopper should land on: whatever is on air right now. */
    public function current(): ?LiveStream
    {
        return LiveStream::query()
            ->live()
            ->withCount('products')
            ->latest('started_at')
            ->first();
    }

    /** On-air plus replays still inside their three-day window. */
    public function visible()
    {
        return LiveStream::query()
            ->visible()
            ->withCount('products')
            ->orderByRaw("CASE WHEN status = 'live' THEN 0 ELSE 1 END")
            ->latest('started_at')
            ->get();
    }

    /**
     * The stream, if a shopper may still open it — and nothing else. Heartbeats,
     * likes and chat all come through here, so the rail is left to withRail():
     * a thousand viewers heartbeating once a minute should not each reload six
     * products with seven relations.
     */
    public function findVisible(int $id): ?LiveStream
    {
        return LiveStream::query()->visible()->find($id);
    }

    /** The product rail in the exact shape the catalogue returns. */
    public function withRail(LiveStream $stream): LiveStream
    {
        $stream->load(['products' => fn ($query) => $query
            ->orderBy('sort_order')
            ->with(['product' => fn ($product) => CatalogueProducts::load($product)])]);

        CatalogueProducts::decorate($stream->products->pluck('product'));

        return $stream;
    }

    public function create(array $data, ?UploadedFile $cover = null): LiveStream
    {
        return DB::transaction(function () use ($data, $cover) {
            $stream = LiveStream::create([
                'title' => $data['title'],
                'cover_path' => $cover ? compressAndUploadImage($cover, 'live', 'cover') : null,
                'orientation' => $data['orientation'] ?? LiveStream::ORIENTATION_LANDSCAPE,
                'youtube_video_id' => $data['youtube_video_id'] ?? null,
                'status' => LiveStream::STATUS_DRAFT,
                'created_by' => auth()->id(),
            ]);

            $this->syncProducts($stream, $data['product_ids'] ?? []);

            return $stream->fresh();
        });
    }

    public function update(LiveStream $stream, array $data, ?UploadedFile $cover = null): LiveStream
    {
        return DB::transaction(function () use ($stream, $data, $cover) {
            $stream->fill(array_filter([
                'title' => $data['title'] ?? null,
                'orientation' => $data['orientation'] ?? null,
            ], fn ($value) => $value !== null));

            // Not folded into the array_filter above: an explicit null here is
            // the admin clearing a link they attached by mistake, and filter
            // would swallow exactly that.
            if (array_key_exists('youtube_video_id', $data)) {
                $stream->youtube_video_id = $data['youtube_video_id'];
            }

            if ($cover) {
                $stream->cover_path = compressAndUploadImage($cover, 'live', 'cover');
            } elseif (filter_var($data['remove_cover'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                $stream->cover_path = null;
            }

            $stream->save();

            if (array_key_exists('product_ids', $data)) {
                $this->syncProducts($stream, $data['product_ids'] ?? []);
            }

            return $stream->fresh();
        });
    }

    /**
     * Replaces the rail while keeping what a product already earned: a row that
     * survives the sync keeps the offset at which it was first presented, so a
     * replay does not lose its timeline when the admin reorders mid-stream.
     */
    public function syncProducts(LiveStream $stream, array $productIds): void
    {
        $productIds = collect($productIds)->filter()->map(fn ($id) => (int) $id)->unique()->values();

        $existing = $stream->products()->get()->keyBy('product_id');

        $stream->products()
            ->whereNotIn('product_id', $productIds->all())
            ->delete();

        foreach ($productIds as $index => $productId) {
            $row = $existing->get($productId);

            if ($row) {
                $row->update(['sort_order' => $index]);
                continue;
            }

            $stream->products()->create([
                'product_id' => $productId,
                'sort_order' => $index,
                'is_active' => false,
            ]);
        }

        if ($stream->active_product_id && ! $productIds->contains($stream->active_product_id)) {
            $stream->update(['active_product_id' => null]);
        }
    }

    /** Adds one product to a rail that is already on air. */
    public function addProduct(LiveStream $stream, int $productId): LiveStreamProduct
    {
        $existing = $stream->products()->where('product_id', $productId)->first();

        if ($existing) {
            return $existing->load('product');
        }

        $row = $stream->products()->create([
            'product_id' => $productId,
            'sort_order' => (int) $stream->products()->max('sort_order') + 1,
            'is_active' => false,
        ]);

        return $row->load('product');
    }

    public function removeProduct(LiveStream $stream, int $productId): void
    {
        $stream->products()->where('product_id', $productId)->delete();

        if ($stream->active_product_id === $productId) {
            $stream->update(['active_product_id' => null]);
            LiveBroadcast::send(new LiveActiveProductChanged($stream->fresh()));
        }
    }

    /**
     * The one-tap action during a broadcast: swap the card under the video.
     * Every open screen is told over the socket rather than polling for it.
     */
    public function setActiveProduct(LiveStream $stream, ?int $productId): LiveStream
    {
        DB::transaction(function () use ($stream, $productId) {
            $stream->products()->update(['is_active' => false]);

            if ($productId !== null) {
                $row = $stream->products()->where('product_id', $productId)->first();

                if (! $row) {
                    $row = $this->addProduct($stream, $productId);
                }

                $row->is_active = true;

                // Recorded once, the first time the product is shown, so the
                // replay can pin the card to the right second of the video.
                if ($row->shown_at === null) {
                    $row->shown_at = now();
                    // Carbon 3 returns a signed difference, so the order is the
                    // point here: asked the other way round it comes out
                    // negative, and max() flattened every offset to zero.
                    $row->shown_at_offset = $stream->started_at
                        ? (int) max(0, $stream->started_at->diffInSeconds(now()))
                        : null;
                }

                $row->save();
            }

            $stream->active_product_id = $productId;
            $stream->save();
        });

        $stream = $stream->fresh();

        LiveBroadcast::send(new LiveActiveProductChanged($stream));

        return $stream;
    }

    /**
     * Marks a stream as on air. Idempotent: the YouTube sync runs every minute
     * and must never announce the same broadcast twice, which is what
     * notified_at guards.
     */
    public function markLive(LiveStream $stream, string $youtubeVideoId, ?string $startedAt = null): LiveStream
    {
        $wasLive = $stream->isLive();

        $stream->forceFill([
            'youtube_video_id' => $youtubeVideoId,
            'status' => LiveStream::STATUS_LIVE,
            'started_at' => $stream->started_at ?? ($startedAt ? \Carbon\Carbon::parse($startedAt) : now()),
            'ended_at' => null,
            'replay_until' => null,
        ])->save();

        $stream = $stream->fresh();

        if (! $wasLive) {
            LiveBroadcast::send(new LiveStreamStatusChanged($stream));
        }

        if ($stream->notified_at === null) {
            $this->notifier->streamStarted($stream);
            $stream->forceFill(['notified_at' => now()])->save();
            $stream = $stream->fresh();
        }

        return $stream;
    }

    /**
     * Ends a stream and opens the replay window. The video itself is left
     * untouched on YouTube — only the app stops showing it once the window
     * closes.
     */
    public function markEnded(LiveStream $stream): LiveStream
    {
        if ($stream->status === LiveStream::STATUS_ENDED) {
            return $stream;
        }

        $stream->forceFill([
            'status' => LiveStream::STATUS_ENDED,
            'ended_at' => now(),
            'replay_until' => now()->addDays((int) config('live.replay_days', LiveStream::REPLAY_DAYS)),
            'viewer_count' => 0,
        ])->save();

        $stream = $stream->fresh();

        LiveBroadcast::send(new LiveStreamStatusChanged($stream));

        return $stream;
    }

    /**
     * Called by the presence channel as viewers come and go. The peak is kept
     * because it is the number worth reporting after the fact.
     */
    public function updateViewerCount(LiveStream $stream, int $count): LiveStream
    {
        $count = max(0, $count);

        $stream->forceFill([
            'viewer_count' => $count,
            'viewer_peak' => max((int) $stream->viewer_peak, $count),
        ])->save();

        $stream = $stream->fresh();

        LiveBroadcast::send(new LiveViewerCountUpdated($stream->id, $stream->viewer_count, $stream->like_count));

        return $stream;
    }

    public function like(LiveStream $stream): LiveStream
    {
        $stream->increment('like_count');
        $stream = $stream->fresh();

        LiveBroadcast::send(new LiveViewerCountUpdated($stream->id, $stream->viewer_count, $stream->like_count));

        return $stream;
    }

    /** @return array<int, int> ids that exist and may legally be shown */
    public function filterSellableProductIds(array $productIds): array
    {
        return Product::query()
            ->publiclyAvailable()
            ->whereIn('id', $productIds)
            ->pluck('id')
            ->all();
    }
}
