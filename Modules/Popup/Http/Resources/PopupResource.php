<?php

namespace Modules\Popup\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PopupResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $type = $this->type ?: ($this->video ? 'video' : 'image');

        return [
            "id" => $this->id,
            "type" => $type,
            "image" => $this->image,
            "video" => $this->video,
            "show_on_home_page" => (bool) $this->show_on_home_page,
        ];
    }
}
