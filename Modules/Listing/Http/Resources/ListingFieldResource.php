<?php

namespace Modules\Listing\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One field of a section, as the ad form and the filter need it.
 *
 * Choices are inlined only when the list is short. A model list runs to
 * thousands of rows and is fetched on demand instead, once a make is picked.
 *
 * @property \Modules\Listing\Http\Entities\ListingAttribute $resource
 */
class ListingFieldResource extends JsonResource
{
    private const INLINE_OPTION_LIMIT = 200;

    public function toArray($request): array
    {
        $optionCount = $this->when(true, fn () => $this->options_count ?? ($this->usesOptions() ? $this->options()->where('is_active', true)->count() : 0));
        $count = is_int($optionCount) ? $optionCount : (int) $optionCount;
        $inline = $this->usesOptions() && $this->parent_id === null && $count > 0 && $count <= self::INLINE_OPTION_LIMIT;

        return [
            'id' => $this->id,
            'key' => $this->key,
            'label' => $this->label,
            'type' => $this->type,
            'unit' => $this->unit,
            'is_required' => $this->is_required,
            'in_filter' => $this->in_filter,
            'in_card' => $this->in_card,
            // A number asked as "from - to" in the filter: price, year, mileage.
            'is_range' => $this->is_range,
            // The field this one follows: model follows make.
            'parent_id' => $this->parent_id,
            // A field with answers behind it is switched off rather than
            // deleted, so the panel has to be able to see that.
            'is_active' => $this->is_active,
            // Same as on a section: one language for the apps, all of them
            // for the panel's form.
            'label_translations' => $this->getTranslations('label'),
            'sort_order' => $this->sort_order,
            // The badge this field's answer stands for, and the answer that
            // earns it. Empty on most fields.
            'badge_key' => $this->badge_key,
            'badge_when' => $this->badge_when,
            'options_count' => $count,
            'options' => $inline
                ? ListingOptionResource::collection($this->options()->where('is_active', true)->get())
                : [],
            'options_url' => $this->usesOptions()
                ? route('api.listings.fields.options', ['field' => $this->id])
                : null,
        ];
    }
}
