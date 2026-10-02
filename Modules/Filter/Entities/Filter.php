<?php

namespace Modules\Filter\Entities;

use Illuminate\Database\Eloquent\Model;
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
