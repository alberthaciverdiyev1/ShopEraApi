<?php

namespace Modules\Chat\Http\Entities;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $fillable = [
        'conversation_id',
        'sender_type',
        'sender_id',
        'message',
        'is_read'
    ];

    public function attachments()
    {
        return $this->hasMany(MessageAttachment::class);
    }
}

