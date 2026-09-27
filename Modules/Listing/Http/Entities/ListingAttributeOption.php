<?php

namespace Modules\Listing\Http\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

/**
 * A choice inside a field. An option may hang off another one, which is how
 * picking BMW narrows the model list to BMW's models.
 */
class ListingAttributeOption extends Model
{
    use HasTranslations;

    protected $table = 'listing_attribute_options';

    protected $guarded = [];

    public array $translatable = ['label'];

    protected $casts = [
        'label' => 'array',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(ListingAttribute::class, 'attribute_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_option_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_option_id');
    }
}
