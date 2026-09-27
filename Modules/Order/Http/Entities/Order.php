<?php

namespace Modules\Order\Http\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\User\Http\Entities\Address;
use Modules\User\Http\Entities\User;

class Order extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'orders';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = [];

    public function items()
    {
        return $this->hasMany(OrderItem::class, 'order_id', 'id');
    }

    public function statuses()
    {
        return $this->hasMany(OrderStatus::class, 'order_id', 'id');
    }

    public function latestStatus()
    {
        return $this->hasOne(OrderStatus::class, 'order_id', 'id')->latestOfMany();
    }

    public function starexShipment()
    {
        return $this->hasOne(StarexShipment::class, 'order_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function address()
    {
        return $this->belongsTo(Address::class, 'address_id', 'id');
    }
    // Order.php
    public function promoCodes()
    {
        return $this->hasManyThrough(
            \Modules\PromoCode\Http\Entities\PromoCode::class,
            \Illuminate\Database\Eloquent\Relations\Pivot::class,
            'order_id', // used_promo_codes.order_id
            'id',       // promo_codes.id
            'id',       // orders.id
            'promo_code_id' // used_promo_codes.promo_code_id
        );
    }

}
