<?php

namespace Modules\Product\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductStoryVideoResource extends JsonResource
{
    public function toArray($request): array
    {
        $product = $this->whenLoaded('product') ? $this->product : null;
        $image = $product?->relationLoaded('images')
            ? $product->images->first()?->image_path
            : null;

        return [
            'id' => $this->id,
            'video_path' => $this->video_path,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'expires_at' => $this->story_expires_at?->format('Y-m-d H:i:s')
                ?? $this->created_at?->copy()->addDay()->format('Y-m-d H:i:s'),
            'is_story_hidden' => (bool) $this->is_story_hidden,
            'is_story_active' => !$this->is_story_hidden && (
                ($this->story_expires_at && $this->story_expires_at->isFuture())
                || (!$this->story_expires_at && $this->created_at && $this->created_at->gte(now()->subDay()))
            ),
            'product' => $product ? [
                'id' => $product->id,
                'title' => $product->title,
                'image' => $image,
                'price' => $product->price !== null ? (float) $product->price : null,
                'discount' => $product->discount !== null ? (float) $product->discount : null,
                'url' => route('storefront.product', ['product' => $product->id]),
            ] : null,
        ];
    }
}
