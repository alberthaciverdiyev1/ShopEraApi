<?php

namespace Modules\Filter\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class FilterResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'type' => $this->type,
            'options' => $this->options ?? [],
            'values' => $this->resource->values ?? [],
        ];
    }
}
