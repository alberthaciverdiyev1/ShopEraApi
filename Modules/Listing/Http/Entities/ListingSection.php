<?php

namespace Modules\Listing\Http\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

/**
 * One kind of ad - cars, property, later jobs - together with the fields and
 * the filter the admin built for it.
 */
class ListingSection extends Model
{
    use HasTranslations, SoftDeletes;

    public const TEMPLATES = ['simple', 'vehicle', 'property'];

    protected $table = 'listing_sections';

    protected $guarded = [];

    public array $translatable = ['name', 'warning_text'];

    protected $casts = [
        'name' => 'array',
        'warning_text' => 'array',
        'auto_approve' => 'boolean',
        'is_active' => 'boolean',
        'duration_days' => 'integer',
        'sort_order' => 'integer',
    ];

    /**
     * Named fields(), not attributes(): Eloquent already keeps the loaded
     * columns in a property called attributes, and a relation of that name
     * would be shadowed by it.
     */
    public function fields(): HasMany
    {
        return $this->hasMany(ListingAttribute::class, 'section_id')->orderBy('sort_order');
    }

    public function listings(): HasMany
    {
        return $this->hasMany(Listing::class, 'section_id');
    }
}
