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
            'description' => $this->description,
            'parent_id' => $this->parent_id,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
            'children' => self::collection($this->whenLoaded('children')),
            'products' => ProductResource::collection($this->whenLoaded('products')),
        ];
    }
}
