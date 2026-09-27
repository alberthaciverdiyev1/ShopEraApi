<?php

namespace Modules\Listing\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Listing\Http\Entities\ListingMedia;
use Modules\Listing\Services\ListingBadgeService;

/**
 * One ad as it appears in a list or on the home rail.
 *
 * Everything shown here comes off the ad itself, including the few fields the
 * section marked for the card, so a page of ads is one query rather than one
 * per ad.
 *
 * @property \Modules\Listing\Http\Entities\Listing $resource
 */
class ListingCardResource extends JsonResource
{
    public function toArray($request): array
    {
        $cardFields = (new ListingSnapshot($this->attribute_values))->cardFields();

        return [
            'id' => $this->id,
            'section_id' => $this->section_id,
            'section_key' => $this->whenLoaded('section', fn () => $this->section->key),
            'title' => $this->title,
            'price' => $this->price === null ? null : (float) $this->price,
            'currency' => $this->currency,
            'is_negotiable' => (bool) $this->is_negotiable,
            'city' => $this->whenLoaded('city', fn () => $this->city?->name),
            'cover_url' => $this->cover(),
            'is_vip' => $this->isVip(),
            'status' => $this->status,
            'published_at' => optional($this->published_at)->toIso8601String(),
            'expires_at' => optional($this->expires_at)->toIso8601String(),
            // The handful of fields the section wants on a card: mileage and
            // year for a car, rooms and floor for a flat.
            'card_fields' => $cardFields,

            // Printed text, so every app renders a card the same way and a new
            // section does not need a release to be formatted properly. Older
            // builds ignore these and format the raw values themselves.
            'price_label' => ListingText::price($this->price === null ? null : (float) $this->price, $this->currency),
            'location_label' => $this->whenLoaded('city', fn () => $this->city?->name),
            'time_label' => ListingText::moment($this->published_at ?? $this->created_at),
            // At most three, which is all a card two columns wide can hold.
            'summary_fields' => array_slice(ListingText::summary($cardFields), 0, 3),
            // Keys only: the icon, colour and wording live in the app.
            'badges' => app(ListingBadgeService::class)->present($this->resource),
        ];
    }

    private function cover(): ?string
    {
        if (! $this->relationLoaded('media')) {
            return null;
        }

        $image = $this->media->firstWhere('type', ListingMedia::TYPE_IMAGE);

        return $image?->url();
    }
}
