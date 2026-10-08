<?php

namespace Modules\Filter\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

/**
 * A value of a filter. Values form a tree: a child (e.g. a model) hangs off
 * its parent (e.g. a brand), so options depend on the parent selection.
 */
class FilterValue extends Model
{
    use HasTranslations;

    protected $table = 'filter_values';

    protected $guarded = [];

    public array $translatable = ['title'];

    protected $casts = [
        'title' => 'array',
    ];

    public function filter(): BelongsTo
    {
        return $this->belongsTo(Filter::class, 'filter_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_value_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_value_id');
    }
}
