<?php

namespace Modules\Manager\Entities;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

/**
 * The platform owner account (operator of the manager panel). Stored in the
 * central `control` database. Access to the panel is gated by the `owner` role.
 *
 * Spatie resolves roles/permissions on the default connection, so the manager
 * panel middleware switches the default connection to `control` for its requests.
 */
class Owner extends Authenticatable
{
    use HasRoles, Notifiable;

    protected $connection = 'control';

    protected $table = 'owners';

    protected string $guard_name = 'owner';

    protected $fillable = ['name', 'email', 'password'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
