<?php

namespace Modules\Setting\Entities;

use Illuminate\Database\Eloquent\Model;

class ThemeColor extends Model
{
    protected $table = 'theme_colors';

    protected $fillable = ['key', 'value', 'label'];

    protected $casts = [
        'key' => 'string',
        'value' => 'string',
    ];
}
