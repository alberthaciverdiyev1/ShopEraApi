<?php

namespace Modules\Live\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Live\Http\Entities\LiveStream;
use Modules\Live\Http\Entities\LiveStreamEvent;
use Modules\Live\Services\LiveStatsService;
use Modules\Order\Http\Entities\Order;

/**
 * Credits an order to the stream that sold it.
 *
 * This runs on the queue rather than inside checkout on purpose: attribution
 * is a reporting concern, and nothing about it is worth risking a customer's
 * order for. If it fails, the order is unaffected and only a statistic is
 * missing.
 *
 * The link is made through the add-to-cart events the app records while a
 * shopper is watching, so the order flow itself never had to change.
 */
class AttributeLiveOrderJob implements ShouldQueue
{
    use Dispatchable, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 30;

    /** How long after leaving a stream a purchase still counts as its sale. */
    private const ATTRIBUTION_WINDOW_HOURS = 24;

    public function __construct(public int $orderId)
    {
    }

    public function handle(LiveStatsService $stats): void
    {
        $order = Order::with('items')->find($this->orderId);

        if (! $order || ! $order->user_id) {
            return;
        }

        $productIds = $order->items->pluck('product_id')->filter()->unique();

        if ($productIds->isEmpty()) {
            return;
        }

        $since = $order->created_at
            ? $order->created_at->copy()->subHours(self::ATTRIBUTION_WINDOW_HOURS)
            : now()->subHours(self::ATTRIBUTION_WINDOW_HOURS);

        $candidates = LiveStreamEvent::query()
            ->where('type', LiveStreamEvent::TYPE_ADD_TO_CART)
            ->where('user_id', $order->user_id)
            ->whereIn('product_id', $productIds)
            ->where('created_at', '>=', $since)
            ->orderByDesc('created_at')
            ->get()
            ->unique('product_id');

        if ($candidates->isEmpty()) {
            return;
        }

        foreach ($candidates as $candidate) {
            $stream = LiveStream::find($candidate->live_stream_id);

            if (! $stream) {
                continue;
            }

            $alreadyCredited = LiveStreamEvent::query()
                ->where('live_stream_id', $stream->id)
                ->where('type', LiveStreamEvent::TYPE_ORDER)
                ->where('order_id', $order->id)
                ->where('product_id', $candidate->product_id)
                ->exists();

            if ($alreadyCredited) {
                continue;
            }

            $item = $order->items->firstWhere('product_id', $candidate->product_id);

            $amount = $item
                ? (float) ($item->total_price ?? ((float) ($item->unit_price ?? 0) * (int) ($item->quantity ?? 1)))
                : null;

            $stats->record(
                $stream,
                LiveStreamEvent::TYPE_ORDER,
                (int) $candidate->product_id,
                (int) $order->user_id,
                (int) $order->id,
                $amount,
            );
        }
    }
}
