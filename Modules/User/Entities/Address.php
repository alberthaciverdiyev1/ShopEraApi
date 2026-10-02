<?php

namespace Modules\User\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Address extends Model
{
    use SoftDeletes;

    protected $table = 'user_addresses';

    protected $guarded = [];

    /**
     * PostgreSQL `numeric` sütunlarını PDO mətn kimi qaytarır; mobil tətbiq
     * isə ədəd gözləyir, ona görə burada çevrilir.
     */
    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
    ];
}
