<?php

namespace Modules\Popup\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PopupResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            "id" => $this->id,
            "image" => $this->image ?: ($this->video ?? ''),
            "video" => $this->video,
            "show_on_home_page" => $this->show_on_home_page
        ];
    }
}
