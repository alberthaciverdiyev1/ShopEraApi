<?php

namespace Modules\User\Http\Entities;

use Illuminate\Database\Eloquent\Model;

class UserReferral extends Model
{
    protected $table = 'user_referrals';

    protected $fillable = [
        'user_id',
        'referral_code'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function referredUsers()
    {
        return $this->hasMany(__CLASS__, 'referral_code', 'referral_code')
            ->with('user');
    }


}
