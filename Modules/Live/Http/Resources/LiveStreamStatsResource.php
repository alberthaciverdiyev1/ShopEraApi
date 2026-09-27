<?php

namespace Modules\Live\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Live\Http\Entities\LiveStream;

/**
 * @property LiveStream $resource
 */
class LiveStreamStatsResource extends JsonResource
{
    /** @param array<string, mixed> $totals */
    public function __construct(LiveStream $resource, private array $totals = [])
    {
        parent::__construct($resource);
    }

    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'status' => $this->status,
            'started_at' => optional($this->started_at)->toIso8601String(),
            'ended_at' => optional($this->ended_at)->toIso8601String(),
            'duration_minutes' => $this->started_at && $this->ended_at
                ? (int) $this->started_at->diffInMinutes($this->ended_at)
                : null,
            'viewer_peak' => (int) $this->viewer_peak,
            'like_count' => (int) $this->like_count,
            'unique_viewers' => (int) ($this->totals['unique_viewers'] ?? 0),
            'chat_messages' => (int) ($this->totals['chat_messages'] ?? 0),
            'product_clicks' => (int) ($this->totals['product_clicks'] ?? 0),
            'add_to_cart' => (int) ($this->totals['add_to_cart'] ?? 0),
            'orders' => (int) ($this->totals['orders'] ?? 0),
            'revenue' => (float) ($this->totals['revenue'] ?? 0),
            'top_products' => $this->totals['top_products'] ?? [],
        ];
    }
}
