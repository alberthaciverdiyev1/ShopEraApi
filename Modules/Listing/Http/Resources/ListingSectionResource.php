<?php

namespace Modules\Listing\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * @property \Modules\Listing\Http\Entities\ListingSection $resource
 */
class ListingSectionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'key' => $this->key,
            'name' => $this->name,
            // Which of the shipped layouts the app should draw: plain,
            // vehicle-style or property-style.
            'template' => $this->template,
            // "{make} {model}" - the ad form builds the title from the answers
            // instead of asking the seller to write one.
            'title_template' => $this->title_template,
            'icon_url' => $this->url($this->icon_path),
            'banner_url' => $this->url($this->banner_path),
            // Shown next to the call button, e.g. advice against paying a
            // deposit up front.
            'warning_text' => $this->warning_text,
            // Whether an ad in this section goes on air at once or waits for
            // a moderator. The panel both shows and edits it.
            'auto_approve' => $this->auto_approve,
            'duration_days' => $this->duration_days,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
            'listing_count' => $this->when(isset($this->listings_count), fn () => (int) $this->listings_count),
            // The apps read `name` in the language they asked for; the panel
            // edits all of them at once and needs the raw map.
            'translations' => [
                'name' => $this->getTranslations('name'),
                'warning_text' => $this->getTranslations('warning_text'),
            ],
            'fields' => ListingFieldResource::collection($this->whenLoaded('fields')),
        ];
    }

    private function url(?string $path): ?string
    {
        return $path ? Storage::disk('public')->url($path) : null;
    }
}
