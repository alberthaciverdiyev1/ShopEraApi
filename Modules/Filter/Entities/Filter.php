<?php

namespace Modules\Filter\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Category\Entities\Category;
use Modules\Product\Entities\Product;
use Spatie\Translatable\HasTranslations;

class Filter extends Model
{
    use HasTranslations;

    protected $table = 'filters';

    public array $translatable = ['title'];

    protected $guarded = [];

    protected $casts = [
        'title' => 'array',
        'options' => 'array',
    ];

    /** The subcategory this filter belongs to. */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    /** The filter whose selection drives this filter's options. */
    public function dependsOn(): BelongsTo
    {
        return $this->belongsTo(self::class, 'depends_on_filter_id');
    }

    public function dependents(): HasMany
    {
        return $this->hasMany(self::class, 'depends_on_filter_id');
    }

    public function values(): HasMany
    {
        return $this->hasMany(FilterValue::class, 'filter_id')->orderBy('sort_order')->orderBy('id');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_filters', 'filter_id', 'category_id');
    }

    public function productValues(): HasMany
    {
        return $this->hasMany(ProductFilter::class, 'filter_id');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_filters', 'filter_id', 'product_id')
            ->withPivot('value');
    }
}
