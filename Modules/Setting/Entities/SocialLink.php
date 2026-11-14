<?php

namespace Modules\Setting\Entities;

use Illuminate\Database\Eloquent\Model;

class SocialLink extends Model
{
    protected $table = 'social_links';

    protected $fillable = [
        'platform',
        'url',
        'sort_order',
        'is_active',
        'show_in_header',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
        'show_in_header' => 'boolean',
    ];
}
