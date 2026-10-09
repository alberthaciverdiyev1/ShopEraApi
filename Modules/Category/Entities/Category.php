<?php

namespace Modules\Category\Entities;

use App\Traits\ImagePath;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Category\Database\Factories\CategoryFactory;
use Modules\Product\Entities\Product;
use Spatie\Translatable\HasTranslations;

class Category extends Model
{
    use HasFactory, HasTranslations, ImagePath, SoftDeletes;

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

    public function parent(): BelongsTo
    {
        return $this->belongsTo(__CLASS__, 'parent_id');
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'category_id');
    }

    /**
     * The full category path, root → … → this category, each entry as
     * ['id' => int, 'name' => string]. Loaded parents are reused; otherwise the
     * chain is walked with one query per level.
     *
     * @return array<int, array{id: int, name: string}>
     */
    public function ancestorChain(): array
    {
        $chain = [];
        $node = $this;
        $guard = 0;

        while ($node && $guard++ < 20) {
            array_unshift($chain, [
                'id' => (int) $node->id,
                'name' => (string) $node->name,
            ]);

            $node = $node->relationLoaded('parent') ? $node->getRelation('parent') : $node->parent()->first();
        }

        return $chain;
    }

    public static function newFactory(): CategoryFactory
    {
        return CategoryFactory::new();
    }
}
