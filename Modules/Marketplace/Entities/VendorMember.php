<?php

namespace Modules\Marketplace\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\User\Entities\User;

/**
 * A user who helps run a store. Permissions are stored as a list of keys
 * from VendorTeamService::PERMISSIONS.
 */
class VendorMember extends Model
{
    protected $table = 'vendor_members';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'permissions' => 'array',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
