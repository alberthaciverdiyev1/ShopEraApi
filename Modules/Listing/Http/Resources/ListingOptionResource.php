<?php

namespace Modules\Listing\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property \Modules\Listing\Http\Entities\ListingAttributeOption $resource
 */
class ListingOptionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'value' => $this->value,
            'label' => $this->label,
            // The make a model hangs off, so the app can narrow the list
            // without asking the server again.
            'parent_option_id' => $this->parent_option_id,
            'sort_order' => $this->sort_order,
            'label_translations' => $this->getTranslations('label'),
            'badge_key' => $this->badge_key,
            // Painted as a swatch in the ad form when the field is a colour.
            'color_hex' => $this->color_hex,
        ];
    }
}
