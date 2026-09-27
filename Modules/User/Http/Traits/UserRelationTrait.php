<?php

namespace Modules\User\Http\Traits;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Balance\Http\Entities\Balance;
use Modules\Notification\Http\Entities\NotificationToken;
use Modules\Product\Http\Entities\Product;
use Modules\Product\Http\Entities\Review;
use Modules\PromoCode\Http\Entities\PromoCode;
use Modules\User\Http\Entities\Address;
use Modules\User\Http\Entities\Basket;
use Modules\User\Http\Entities\ReferralCode;
use Modules\User\Http\Entities\UserReferral;
use Modules\Store\Http\Entities\Store;

trait UserRelationTrait
{
    public function store()
    {
        return $this->hasOne(Store::class);
    }
    public function favorites()
    {
        return $this->belongsToMany(
            Product::class,
            'user_favorites',
            'user_id',
            'product_id'
        )->withTimestamps();
    }

    public function cartItems()
    {
        return $this->belongsToMany(
            Product::class,
            'user_carts',
            'user_id',
            'product_id'
        )
            ->withPivot('quantity')
            ->withTimestamps();
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    public function basket(): HasMany
    {
        return $this->hasMany(Basket::class);
    }

    public function balance(): HasMany
    {
        return $this->hasMany(Balance::class, 'user_id', 'id');
    }

    public function usedPromoCodes()
    {
        return $this->belongsToMany(
            PromoCode::class,
            'used_promo_codes',
            'user_id',
            'promo_code_id'
        )->withTimestamps()->withPivot('id');
    }

    public function stockSubscriptions()
    {
        return $this->belongsToMany(Product::class, 'product_stock_subscriptions')
            ->withPivot('notified_at')
            ->withTimestamps();
    }

    public function notificationTokens()
    {
        return $this->hasMany(NotificationToken::class, 'user_id');
    }

    public function referralCode()
    {
        return $this->hasOne(ReferralCode::class, 'user_id');
    }

    public function userReferral()
    {
        return $this->hasOne(UserReferral::class, 'user_id');
    }

}
