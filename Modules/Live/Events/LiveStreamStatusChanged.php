<?php

namespace Modules\Live\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Live\Http\Entities\LiveStream;

class LiveStreamStatusChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public LiveStream $stream)
    {
    }

    public function broadcastOn(): Channel
    {
        return new Channel('live.' . $this->stream->id);
    }

    public function broadcastAs(): string
    {
        return 'stream.status';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->stream->id,
            'status' => $this->stream->status,
            'youtube_video_id' => $this->stream->youtube_video_id,
            'started_at' => optional($this->stream->started_at)->toIso8601String(),
            'ended_at' => optional($this->stream->ended_at)->toIso8601String(),
            'replay_until' => optional($this->stream->replay_until)->toIso8601String(),
        ];
    }
}
