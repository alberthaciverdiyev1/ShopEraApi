<?php

namespace Modules\Store\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One purchased line of an order, from the merchant's point of view: enough to
 * identify and hand over the physical item without exposing customer data.
 */
class StoreOrderItemResource extends JsonResource
{
    public function toArray($request): array
    {
        $product = $this->product; // relation is withTrashed(), so deleted products still resolve

        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'title' => $product ? ucfirst($product->title) : null,
            'sku' => $product?->sku,
            'image' => $product?->images?->first()?->image_path,
            'is_deleted' => (bool) $product?->deleted_at,
            'color' => $this->color?->name,
            'size' => $this->size?->name,
            'quantity' => (int) $this->quantity,
            'unit_price' => (float) $this->unit_price,
            'total_price' => (float) $this->total_price,
        ];
    }
}
