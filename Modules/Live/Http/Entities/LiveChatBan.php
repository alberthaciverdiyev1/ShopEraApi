<?php

namespace Modules\Live\Http\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\User\Http\Entities\User;

class LiveChatBan extends Model
{
    protected $guarded = [];

    protected $casts = [
        'muted_until' => 'datetime',
        'is_blocked' => 'boolean',
    ];

    public function liveStream(): BelongsTo
    {
        return $this->belongsTo(LiveStream::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** A mute expires on its own; a block stays until an admin lifts it. */
    public function isActive(): bool
    {
        if ($this->is_blocked) {
            return true;
        }

        return $this->muted_until !== null && $this->muted_until->isFuture();
    }
}
