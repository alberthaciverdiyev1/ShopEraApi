<?php

namespace Modules\Live\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Live\Http\Entities\LiveChatMessage;
use Modules\Live\Http\Resources\LiveChatMessageResource;

/**
 * Broadcast without the queue on purpose. A single database-queue worker
 * serialises every job on the box; routing chat through it would put seconds
 * between typing a message and seeing it. Reverb sits on localhost, so
 * publishing inline costs about a millisecond.
 */
class LiveChatMessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public LiveChatMessage $message)
    {
    }

    public function broadcastOn(): Channel
    {
        return new Channel('live.' . $this->message->live_stream_id . '.chat');
    }

    public function broadcastAs(): string
    {
        return 'chat.message';
    }

    public function broadcastWith(): array
    {
        return (new LiveChatMessageResource($this->message))->resolve();
    }
}
