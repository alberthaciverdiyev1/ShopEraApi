<?php

namespace Modules\Product\Http\Entities;

use Illuminate\Broadcasting\Channel;
use Illuminate\Database\Eloquent\BroadcastsEvents;
use Illuminate\Database\Eloquent\Model;

class AiPhoto extends Model
{
    use BroadcastsEvents;

    protected $fillable = ['image_path', 'title', 'description', 'status'];

    protected $casts = [
        'image_path' => 'array',
        'title' => 'array',
        'description' => 'array',
    ];

    public function broadcastOn($event)
    {
        return new Channel('ai-photo.' . $this->id);
    }

    public function broadcastAs($event)
    {
        return 'AiStatusUpdated';
    }
}
