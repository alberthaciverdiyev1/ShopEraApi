<?php

namespace Modules\Live\Http\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\User\Http\Entities\User;

class LiveChatMessage extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'is_admin' => 'boolean',
    ];

    public function liveStream(): BelongsTo
    {
        return $this->belongsTo(LiveStream::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
