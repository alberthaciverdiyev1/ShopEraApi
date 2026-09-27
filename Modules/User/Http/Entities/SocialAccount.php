<?php

namespace Modules\User\Http\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A Google or Apple identity linked to an account, keyed by the provider's
 * stable user id (the `sub` of its tokens).
 */
class SocialAccount extends Model
{
    protected $fillable = [
        'user_id',
        'provider',
        'provider_user_id',
        'refresh_token',
    ];

    protected $hidden = [
        'refresh_token',
    ];

    protected function casts(): array
    {
        return [
            // Apple's refresh token reaches the user's Apple ID data.
            'refresh_token' => 'encrypted',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
