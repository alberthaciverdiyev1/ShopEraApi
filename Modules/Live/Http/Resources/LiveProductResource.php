<?php

namespace Modules\Live\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Product\Http\Resources\ProductResource;

/**
 * Wraps a live_stream_products row. The catalogue product keeps the exact
 * shape the app already parses everywhere else, so nothing new has to be
 * learned to render a card inside a stream.
 *
 * @property \Modules\Live\Http\Entities\LiveStreamProduct $resource
 */
class LiveProductResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'sort_order' => (int) $this->sort_order,
            'is_active' => (bool) $this->is_active,

            // Seconds from the start of the video. The replay pins the product
            // back to the moment it was presented.
            'shown_at_offset' => $this->shown_at_offset,
            'product' => $this->product
                ? (new ProductResource($this->product))->resolve($request)
                : null,
        ];
    }
}
