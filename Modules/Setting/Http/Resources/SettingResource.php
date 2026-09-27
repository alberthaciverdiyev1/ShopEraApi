<?php

namespace Modules\Setting\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SettingResource extends JsonResource
{
    public function toArray($request): array
    {
        return parent::toArray($request);
    }
}
