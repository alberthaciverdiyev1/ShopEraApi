<?php

namespace Modules\Live\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The light shape used for lists and for the "is anything on air?" poll the
 * app makes on startup.
 *
 * @property \Modules\Live\Http\Entities\LiveStream $resource
 */
class LiveStreamListResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'cover_path' => $this->cover_path,
            'youtube_video_id' => $this->youtube_video_id,
            'orientation' => $this->orientation ?: 'landscape',
            'status' => $this->status,
            'is_live' => $this->status === 'live',
            'viewer_count' => (int) $this->viewer_count,
            'like_count' => (int) $this->like_count,
            'product_count' => $this->products_count ?? $this->products()->count(),
            'started_at' => optional($this->started_at)->toIso8601String(),
            'ended_at' => optional($this->ended_at)->toIso8601String(),
            'replay_until' => optional($this->replay_until)->toIso8601String(),
        ];
    }
}
