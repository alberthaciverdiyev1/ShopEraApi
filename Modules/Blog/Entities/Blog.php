<?php

namespace Modules\Blog\Entities;

use App\Traits\ImagePath;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class Blog extends Model
{
    use HasFactory, HasTranslations, ImagePath, SoftDeletes;

    protected $table = 'blogs';

    public array $translatable = ['title', 'description', 'content'];

    protected $guarded = [];

    protected $casts = [
        'title' => 'array',
        'description' => 'array',
        'content' => 'array',
        'tags' => 'array',
        'is_active' => 'boolean',
        'views' => 'integer',
        'published_at' => 'datetime',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
