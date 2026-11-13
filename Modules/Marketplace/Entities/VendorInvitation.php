<?php

namespace Modules\Marketplace\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One shareable link the store owner creates to add a team member with a
 * chosen set of permissions.
 */
class VendorInvitation extends Model
{
    protected $table = 'vendor_invitations';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'permissions' => 'array',
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    public function isUsable(): bool
    {
        return $this->accepted_at === null
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }
}
