<?php

namespace Modules\Live\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Everything one screen needs in a single call: the video, the product rail,
 * the active product and the channel names to subscribe to.
 *
 * @property \Modules\Live\Http\Entities\LiveStream $resource
 */
class LiveStreamResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'cover_path' => $this->cover_path,
            'youtube_video_id' => $this->youtube_video_id,
            'orientation' => $this->orientation ?: 'landscape',

            // Built here so the app never hardcodes the domain, and so the
            // embed can be re-tuned server-side without another release. The
            // app appends its own display options.
            'player_url' => $this->youtube_video_id
                ? route('live.player', ['video' => $this->youtube_video_id])
                : null,

            'status' => $this->status,
            'is_live' => $this->status === 'live',
            'viewer_count' => (int) $this->viewer_count,
            'viewer_peak' => (int) $this->viewer_peak,
            'like_count' => (int) $this->like_count,
            'active_product_id' => $this->active_product_id,
            'started_at' => optional($this->started_at)->toIso8601String(),
            'ended_at' => optional($this->ended_at)->toIso8601String(),
            'replay_until' => optional($this->replay_until)->toIso8601String(),

            // Named here so the client never has to build channel strings by
            // hand and drift from what the server broadcasts on.
            //
            // Both are public channels, which is what lets a guest watch and
            // read the chat without an account: a public subscription never
            // goes through the broadcasting auth endpoint. The audience figure
            // is counted server-side from heartbeats and pushed down the same
            // channel, so there is no presence channel to authorise either.
            'channels' => [
                'stream' => 'live.' . $this->id,
                'chat' => 'live.' . $this->id . '.chat',
            ],

            'products' => LiveProductResource::collection(
                $this->whenLoaded('products')
            ),
        ];
    }
}
