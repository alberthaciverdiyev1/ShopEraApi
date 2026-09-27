<?php

namespace Modules\Listing\Http\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

/**
 * One field of a section: "Marka", "Yürüş", "Otaq sayı". It decides what the
 * ad form asks, what the filter offers and what the card shows.
 */
class ListingAttribute extends Model
{
    use HasTranslations;

    public const TYPES = ['text', 'number', 'select', 'multiselect', 'boolean', 'date', 'youtube', 'location'];

    /** The types whose answers are picked from listing_attribute_options. */
    public const OPTION_TYPES = ['select', 'multiselect'];

    protected $table = 'listing_attributes';

    protected $guarded = [];

    public array $translatable = ['label'];

    protected $casts = [
        'label' => 'array',
        'is_required' => 'boolean',
        'in_filter' => 'boolean',
        'in_card' => 'boolean',
        'is_range' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function section(): BelongsTo
    {
        return $this->belongsTo(ListingSection::class, 'section_id');
    }

    public function options(): HasMany
    {
        return $this->hasMany(ListingAttributeOption::class, 'attribute_id')->orderBy('sort_order');
    }

    /** The field this one follows: model follows make. */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function usesOptions(): bool
    {
        return in_array($this->type, self::OPTION_TYPES, true);
    }
}
