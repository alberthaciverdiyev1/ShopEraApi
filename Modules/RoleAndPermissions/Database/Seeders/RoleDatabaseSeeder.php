<?php

namespace Modules\RoleAndPermissions\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleDatabaseSeeder extends Seeder
{
    protected string $guard = 'sanctum';

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => $this->guard]);
        Role::firstOrCreate(['name' => 'user', 'guard_name' => $this->guard]);
        Role::firstOrCreate(['name' => 'developer', 'guard_name' => $this->guard]);
        Role::firstOrCreate(['name' => 'manager', 'guard_name' => $this->guard]);
    }
}
