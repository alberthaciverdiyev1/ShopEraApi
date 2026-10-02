<?php

namespace Modules\Delivery\Entities;

use Illuminate\Database\Eloquent\Model;

class DeliveryInfo extends Model
{
    protected $table = 'delivery_infos';

    protected $fillable = [
        'type',
        'description',
    ];

    protected $casts = [
        'description' => 'array',
    ];
}
