<?php

namespace Modules\Live\Services;

use Modules\Live\Http\Entities\LiveStream;
use Modules\Live\Http\Entities\LiveStreamEvent;
use Modules\Product\Http\Entities\Product;

class LiveStatsService
{
    public function __construct(private LiveViewerCounter $viewers)
    {
    }

    public function record(
        LiveStream $stream,
        string $type,
        ?int $productId = null,
        ?int $userId = null,
        ?int $orderId = null,
        ?float $amount = null,
    ): void {
        LiveStreamEvent::create([
            'live_stream_id' => $stream->id,
            'product_id' => $productId,
            'order_id' => $orderId,
            'user_id' => $userId,
            'type' => $type,
            'amount' => $amount,
            'created_at' => now(),
        ]);
    }

    /**
     * One pass over the event log per stream. Every figure the statistics
     * screen shows comes from here.
     *
     * @return array<string, mixed>
     */
    public function totals(LiveStream $stream): array
    {
        $counts = LiveStreamEvent::query()
            ->where('live_stream_id', $stream->id)
            ->selectRaw('type, count(*) as total, coalesce(sum(amount), 0) as amount')
            ->groupBy('type')
            ->get()
            ->keyBy('type');

        return [
            // From heartbeats rather than view events: those only know
            // signed-in users, so a guest audience would have counted as nobody.
            'unique_viewers' => $this->viewers->unique($stream),
            'chat_messages' => $stream->messages()->withTrashed()->count(),
            'product_clicks' => (int) ($counts[LiveStreamEvent::TYPE_PRODUCT_CLICK]->total ?? 0),
            'add_to_cart' => (int) ($counts[LiveStreamEvent::TYPE_ADD_TO_CART]->total ?? 0),
            'orders' => (int) ($counts[LiveStreamEvent::TYPE_ORDER]->total ?? 0),
            'revenue' => (float) ($counts[LiveStreamEvent::TYPE_ORDER]->amount ?? 0),
            'top_products' => $this->topProducts($stream),
        ];
    }

    /**
     * Ranked by what a product actually sold, then by how close it came.
     *
     * @return array<int, array<string, mixed>>
     */
    private function topProducts(LiveStream $stream, int $limit = 10): array
    {
        $rows = LiveStreamEvent::query()
            ->where('live_stream_id', $stream->id)
            ->whereNotNull('product_id')
            ->selectRaw('product_id')
            ->selectRaw('count(*) filter (where type = ?) as clicks', [LiveStreamEvent::TYPE_PRODUCT_CLICK])
            ->selectRaw('count(*) filter (where type = ?) as carts', [LiveStreamEvent::TYPE_ADD_TO_CART])
            ->selectRaw('count(*) filter (where type = ?) as orders', [LiveStreamEvent::TYPE_ORDER])
            ->selectRaw('coalesce(sum(amount) filter (where type = ?), 0) as revenue', [LiveStreamEvent::TYPE_ORDER])
            ->groupBy('product_id')
            ->orderByDesc('orders')
            ->orderByDesc('carts')
            ->orderByDesc('clicks')
            ->limit($limit)
            ->get();

        if ($rows->isEmpty()) {
            return [];
        }

        $titles = Product::withTrashed()
            ->whereIn('id', $rows->pluck('product_id'))
            ->get(['id', 'title'])
            ->keyBy('id');

        return $rows->map(fn ($row) => [
            'product_id' => (int) $row->product_id,
            'title' => (string) (optional($titles->get($row->product_id))->title ?? ''),
            'clicks' => (int) $row->clicks,
            'add_to_cart' => (int) $row->carts,
            'orders' => (int) $row->orders,
            'revenue' => (float) $row->revenue,
        ])->all();
    }
}
