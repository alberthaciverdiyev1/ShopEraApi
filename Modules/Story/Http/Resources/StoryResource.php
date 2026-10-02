<?php

namespace Modules\Story\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $product = $this->whenLoaded('product');
        $productImage = null;

        if ($product && $product->relationLoaded('images')) {
            $productImage = $product->images->first()?->image_path;
        }

        return [
            'id' => $this->id,
            'image' => $this->image,
            'video' => $this->video,
            'product_id' => $this->product_id,
            'expires_at' => $this->expires_at?->toIso8601String(),
            'product' => $this->whenLoaded('product', fn () => $product ? [
                'id' => $product->id,
                'title' => $product->title,
                'price' => $product->price,
                'discount' => $product->discount,
                'image' => $productImage,
            ] : null),
        ];
    }
}
