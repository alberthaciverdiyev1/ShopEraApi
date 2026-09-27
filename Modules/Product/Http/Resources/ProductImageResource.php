<?php

namespace Modules\Product\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductImageResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            "id"=>$this->id,
            "image_path"=>$this->image_path,
            "color_id" => $this->color_id
        ];
    }
}
