<?php

namespace Modules\Delivery\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Delivery\Database\Factories\DeliveryFactory;

class Delivery extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'delivery_prices';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'city_name',
        'price',
        'free_from',
        'delivery_time',
        'fast_delivery_time',
        'is_active',
        'fast_price',
    ];

    protected static function newFactory(): DeliveryFactory
    {
        return DeliveryFactory::new();
    }
}
