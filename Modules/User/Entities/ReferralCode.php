<?php

namespace Modules\User\Entities;

use Illuminate\Database\Eloquent\Model;

class ReferralCode extends Model
{
    protected $table = 'user_referral_codes';

    protected $fillable = [
        'referral_code',
        'usage_count',
        'user_id',
    ];
}
