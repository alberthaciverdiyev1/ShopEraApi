<?php

namespace Modules\HelpAndPolicy\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LegalTermResource extends JsonResource
{
    public function toArray(Request $request)
    {
        return [
            "id" => $this->id,
            "type" => $this->type,
            "html" => $this->html,
        ];
    }
}
