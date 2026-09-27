<?php

namespace Modules\Color\Http\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class ColorResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            "id" => $this->id,
            "name" => $this->name,
            "hex" => $this->hex,
            "is_active" => $this->is_active,
            "sort_order" => $this->sort_order,
        ];
    }
}
