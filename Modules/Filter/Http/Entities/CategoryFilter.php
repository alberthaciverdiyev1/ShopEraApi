<?php

namespace Modules\Filter\Http\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Category\Http\Entities\Category;

class CategoryFilter extends Model
{
    protected $table = 'category_filters';

    protected $guarded = [];

    public function filter(): BelongsTo
    {
        return $this->belongsTo(Filter::class, 'filter_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }
}
