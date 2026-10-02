<?php

namespace Modules\Manager\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Modules\Manager\Entities\Owner;
use Spatie\Permission\Models\Role;

/**
 * Creates the single platform `owner` role and account. Only this account may
 * access the manager panel.
 */
class OwnerSeeder extends Seeder
{
    public function run(): void
    {
        // Spatie resolves roles/permissions on the default connection, so point
        // it at the central control DB for the duration of this seeder.
        $previous = DB::getDefaultConnection();
        DB::setDefaultConnection('control');

        try {
            $role = Role::firstOrCreate(['name' => 'owner', 'guard_name' => 'owner']);

            $owner = Owner::query()->updateOrCreate(
                ['email' => (string) env('MANAGER_OWNER_EMAIL', 'owner@snaker.store')],
                [
                    'name' => (string) env('MANAGER_OWNER_NAME', 'Snaker Owner'),
                    'password' => Hash::make((string) env('MANAGER_OWNER_PASSWORD', 'password')),
                ]
            );

            if (! $owner->hasRole('owner')) {
                $owner->assignRole($role);
            }
        } finally {
            DB::setDefaultConnection($previous);
        }
    }
}
