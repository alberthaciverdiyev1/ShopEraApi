<?php

namespace Modules\Live\Http\Entities;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Product\Http\Entities\Product;
use Modules\User\Http\Entities\User;

class LiveStream extends Model
{
    use SoftDeletes;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_LIVE = 'live';
    public const STATUS_ENDED = 'ended';

    /** How the broadcast is framed — decides the app's whole screen layout. */
    public const ORIENTATION_LANDSCAPE = 'landscape';
    public const ORIENTATION_PORTRAIT = 'portrait';

    public const ORIENTATIONS = [self::ORIENTATION_LANDSCAPE, self::ORIENTATION_PORTRAIT];

    /** How long a finished stream stays watchable inside the app. */
    public const REPLAY_DAYS = 3;

    protected $guarded = [];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'replay_until' => 'datetime',
        'notified_at' => 'datetime',
        'viewer_count' => 'integer',
        'viewer_peak' => 'integer',
        'like_count' => 'integer',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(LiveStreamProduct::class);
    }

    public function catalogueProducts(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'live_stream_products')
            ->withPivot(['sort_order', 'is_active', 'shown_at_offset', 'shown_at'])
            ->withTimestamps();
    }

    public function activeProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'active_product_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(LiveChatMessage::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(LiveStreamEvent::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeLive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_LIVE);
    }

    /**
     * Everything a shopper may still open: the stream that is on air right now
     * plus finished ones whose three-day replay window has not closed.
     *
     * The window is a query filter rather than a cleanup job on purpose — a job
     * that fails leaves expired replays on screen, a filter never can.
     */
    public function scopeVisible(Builder $query): Builder
    {
        return $query->where(function (Builder $query) {
            $query
                ->where('status', self::STATUS_LIVE)
                ->orWhere(function (Builder $query) {
                    $query
                        ->where('status', self::STATUS_ENDED)
                        ->whereNotNull('replay_until')
                        ->where('replay_until', '>', now());
                });
        });
    }

    public function isLive(): bool
    {
        return $this->status === self::STATUS_LIVE;
    }

    public function isReplayable(): bool
    {
        return $this->status === self::STATUS_ENDED
            && $this->replay_until !== null
            && $this->replay_until->isFuture();
    }

    /** Public channel name shared by the app and the admin panel. */
    public function channelName(): string
    {
        return 'live.' . $this->id;
    }
}
