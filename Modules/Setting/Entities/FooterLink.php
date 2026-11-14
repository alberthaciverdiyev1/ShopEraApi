<?php

namespace Modules\Setting\Entities;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class FooterLink extends Model
{
    use HasTranslations;

    protected $table = 'footer_links';

    public array $translatable = ['label'];

    protected $fillable = [
        'section',
        'label',
        'url',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];
}
