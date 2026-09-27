<?php

namespace Modules\PromoCode\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PromoCodeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return parent::toArray($request);
    }

}
