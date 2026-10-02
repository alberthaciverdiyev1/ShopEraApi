<?php

namespace Modules\Filter\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductFilterResource extends JsonResource
{
    public function toArray($request): array
    {
        $locale = app()->getLocale();
        $title = $this->filter?->getTranslation('title', $locale, false)
            ?? $this->filter?->getTranslation('title', 'az', false)
            ?? (is_array($this->filter?->title) ? reset($this->filter->title) : $this->filter?->title);

        return [
            'id' => $this->filter_id,
            'filter_id' => $this->filter_id,
            'name' => $title,
            'title' => $title,
            'value' => $this->value,
        ];
    }
}
