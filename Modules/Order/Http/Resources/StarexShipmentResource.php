<?php

namespace Modules\Order\Http\Resources;

use App\Services\Starex\StarexStatusTranslator;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StarexShipmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'tracking_number' => $this->tracking_number,
            'delivery_type' => $this->delivery_type,
            'sync_status' => $this->sync_status,
            'status' => $this->external_status,
            'status_label' => StarexStatusTranslator::label($this->external_status),
            'last_error' => $this->when($request->user()?->hasAnyRole(['admin', 'manager', 'developer']), $this->last_error),
            'sent_at' => $this->sent_at,
            'cancelled_at' => $this->cancelled_at,
            'last_event_at' => $this->last_event_at,
            'events' => $this->whenLoaded('events', function () {
                return $this->events
                    ->sortByDesc('event_date')
                    ->values()
                    ->map(fn($event) => [
                        'event_id' => $event->event_id,
                        'status' => $event->status,
                        'status_label' => StarexStatusTranslator::label($event->status),
                        'event_date' => $event->event_date,
                    ]);
            }),
        ];
    }
}
