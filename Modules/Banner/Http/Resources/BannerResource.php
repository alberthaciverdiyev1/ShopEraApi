<?php

namespace Modules\Banner\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Product\Http\Resources\ProductResource;

class BannerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'image' => $this->image,
            'second_image' => $this->second_image,
            'type' => $this->type,
            'url' => $this->url,
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'product_id' => $this->product_id,
            'is_active' => (bool) $this->is_active,
            'product' => new ProductResource($this->whenLoaded('product')),
        ];
    }
}
