<?php

namespace Modules\Color\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Color\Database\Factories\ColorFactory;
use Modules\Product\Entities\Product;
use Spatie\Translatable\HasTranslations;

class Color extends Model
{
    use HasFactory, HasTranslations,SoftDeletes;

    public array $translatable = ['name'];

    protected $guarded = [];

    protected $casts = [
        'name' => 'array',
    ];

    public function products()
    {
        return $this->belongsToMany(Product::class);
    }

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'colors';

    public static function newFactory(): ColorFactory
    {
        return ColorFactory::new();
    }
}
