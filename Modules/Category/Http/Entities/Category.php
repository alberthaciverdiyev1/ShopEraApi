<?php

namespace Modules\Category\Http\Entities;

use App\Traits\ImagePath;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Category\Database\Factories\CategoryFactory;
use Modules\Product\Http\Entities\Product;
use Spatie\Translatable\HasTranslations;

class Category extends Model
{
    use HasTranslations, HasFactory, SoftDeletes, ImagePath;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'categories';
    public array $translatable = ['name'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = [];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'name' => 'array',

    ];

    public function children()
    {
        return $this->hasMany(__CLASS__, 'parent_id');
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'category_id');
    }

    public static function newFactory(): CategoryFactory
    {
        return CategoryFactory::new();
    }

}
