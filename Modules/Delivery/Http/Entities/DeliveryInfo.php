<?php

namespace Modules\Delivery\Http\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Delivery\Database\Factories\DeliveryFactory;

class DeliveryInfo extends Model
{
    protected $table = 'delivery_infos';


    protected $fillable = [
        'type',
        'description',
    ];

    protected $casts = [
        'description' =>'array',
    ];
}
