<?php

namespace Modules\Filter\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductFilterResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'filter_id' => $this->filter_id,
            'value' => $this->value,
        ];
    }
}
