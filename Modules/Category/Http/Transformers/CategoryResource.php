<?php

namespace Modules\Category\Http\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Product\Http\Resources\ProductResource;

class CategoryResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'image' => $this->image,
            'background_color' => $this->background_color,
            'description' => $this->description,
            'parent_id' => $this->parent_id,
            'needs_brand' => (bool) $this->needs_brand,
            'show_on_home' => (bool) $this->show_on_home,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
            'products_count' => $this->whenCounted('products'),
            'children' => self::collection($this->whenLoaded('children')),
            'products' => ProductResource::collection($this->whenLoaded('products')),
        ];
    }
}
