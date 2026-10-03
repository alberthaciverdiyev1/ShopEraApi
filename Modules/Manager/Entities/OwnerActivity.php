<?php

namespace Modules\Manager\Entities;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OwnerActivity extends ControlModel
{
    protected $table = 'owner_activities';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function siteOwner(): BelongsTo
    {
        return $this->belongsTo(SiteOwner::class);
    }

    /** Records one activity row for an owner (best-effort, never throws). */
    public static function record(?SiteOwner $owner, string $action, ?string $description = null, ?string $ip = null): void
    {
        if (! $owner) {
            return;
        }

        try {
            static::query()->create([
                'site_owner_id' => $owner->id,
                'action' => $action,
                'description' => $description,
                'ip' => $ip,
                'created_at' => now(),
            ]);
        } catch (\Throwable) {
            // Activity logging must never break the request.
        }
    }
}
