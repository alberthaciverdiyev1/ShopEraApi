<?php

namespace Modules\Delivery\Entities;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

/**
 * @mixin Builder
 * @mixin \Illuminate\Database\Query\Builder
 */
class PickupPoint extends Model
{
    use HasTranslations,SoftDeletes;

    protected $table = 'pickup_points';

    public array $translatable = ['delivery_time'];

    protected $fillable = [
        'name',
        'address',
        'price',
        'is_active',
        'delivery_time',
    ];

    protected array $dates = ['deleted_at', 'created_at', 'updated_at'];

    protected $casts = [
        'is_active' => 'boolean',
        'price' => 'float',
        'name' => 'string',
        'address' => 'string',
        'delivery_time' => 'array',
    ];
}
