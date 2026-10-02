<?php

namespace Modules\Notification\Entities;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\User\Entities\User;

class NotificationToken extends Model
{
    protected $fillable = [
        'user_id',
        'token',
        'device_type',
        'is_active',
        'last_used_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_used_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive(Builder $query)
    {
        return $query->where('is_active', true);
    }
}
