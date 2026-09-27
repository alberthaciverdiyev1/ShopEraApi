<?php

namespace Modules\Chat\Http\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MessageAttachment extends Model
{

    protected $table = 'message_attachments';

    protected $fillable = [
        'message_id',
        'path',
    ];

    protected $casts = [
        'message_id' => 'integer',
    ];

    public function message()
    {
        return $this->belongsTo(Message::class);
    }


    public function getUrlAttribute(): string
    {
        return asset('storage/' . $this->path);
    }

}
