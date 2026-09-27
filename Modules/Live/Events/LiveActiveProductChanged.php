<?php

namespace Modules\Live\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Live\Http\Entities\LiveStream;
use Modules\Live\Http\Resources\LiveProductResource;
use Modules\Live\Support\CatalogueProducts;

/**
 * The admin taps a product and every open screen has to swap at once, so this
 * one also skips the queue.
 */
class LiveActiveProductChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public LiveStream $stream)
    {
    }

    public function broadcastOn(): Channel
    {
        return new Channel('live.' . $this->stream->id);
    }

    public function broadcastAs(): string
    {
        return 'product.changed';
    }

    public function broadcastWith(): array
    {
        $pivot = $this->stream->products()
            ->with(['product' => fn ($product) => CatalogueProducts::load($product)])
            ->where('product_id', $this->stream->active_product_id)
            ->first();

        // The same push reaches every screen, so nothing in it is personal.
        CatalogueProducts::decorate([$pivot?->product], forViewer: false);

        return [
            'active_product_id' => $this->stream->active_product_id,
            'active_product' => $pivot && $pivot->product
                ? (new LiveProductResource($pivot))->resolve()
                : null,
        ];
    }
}
