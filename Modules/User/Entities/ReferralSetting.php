<?php

namespace Modules\User\Entities;

use Illuminate\Database\Eloquent\Model;

class ReferralSetting extends Model
{
    protected $table = 'referral_setting';

    protected $fillable = [
        'referral_amount',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'referral_amount' => 'decimal:2',
    ];
}
