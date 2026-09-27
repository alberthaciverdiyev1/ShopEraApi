<?php

namespace Modules\Banner\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BannerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'image' => $this->image,
            'second_image' => $this->second_image,
            'type'=>$this->type,
            'url' => $this->url
        ];
    }
}
