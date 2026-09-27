<?php

namespace Modules\Live\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property \Modules\Live\Http\Entities\LiveChatMessage $resource
 */
class LiveChatMessageResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'live_stream_id' => $this->live_stream_id,
            'user_id' => $this->user_id,
            'author_name' => $this->author_name,
            'is_admin' => (bool) $this->is_admin,
            'body' => $this->body,
            'created_at' => optional($this->created_at)->toIso8601String(),
        ];
    }
}
