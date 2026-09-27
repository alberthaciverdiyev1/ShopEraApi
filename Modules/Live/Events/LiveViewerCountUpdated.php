<?php

namespace Modules\Live\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LiveViewerCountUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $liveStreamId,
        public int $viewerCount,
        public int $likeCount,
    ) {
    }

    public function broadcastOn(): Channel
    {
        return new Channel('live.' . $this->liveStreamId);
    }

    public function broadcastAs(): string
    {
        return 'viewers.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'viewer_count' => $this->viewerCount,
            'like_count' => $this->likeCount,
        ];
    }
}
