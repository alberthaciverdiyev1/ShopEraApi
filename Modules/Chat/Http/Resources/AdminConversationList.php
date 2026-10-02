<?php

namespace Modules\Chat\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminConversationList extends JsonResource
{
    public function toArray(Request $request)
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'user_name' => $this->user?->name,
            'user_surname' => $this->user?->surname,
            'user_email' => $this->user?->email,
            'admin_id' => $this->admin_id,
            'last_message_at' => $this->last_message_at,
            'unread_count' => $this->unread_count,
        ];
    }
}
