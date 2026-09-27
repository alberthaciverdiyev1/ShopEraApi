<?php

namespace Modules\Live\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LiveChatMessageRemoved implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public int $liveStreamId, public int $messageId)
    {
    }

    public function broadcastOn(): Channel
    {
        return new Channel('live.' . $this->liveStreamId . '.chat');
    }

    public function broadcastAs(): string
    {
        return 'chat.removed';
    }

    public function broadcastWith(): array
    {
        return ['id' => $this->messageId];
    }
}
