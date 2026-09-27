<?php

namespace Modules\Brand\Http\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Product\Http\Resources\ProductResource;

class BrandDetailsResource extends JsonResource
{
    public function toArray(Request $request)
    {
        return [
            'id'=>$this->id,
            'name'=>$this->name,
            'image'=>$this->image,
            'is_active'=>$this->is_active,
            'sort_order'=>$this->sort_order,
            'products'=> ProductResource::collection($this->whenLoaded('products'))
        ];
    }

}
