<?php

namespace Modules\RoleAndPermissions\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionDatabaseSeeder extends Seeder
{
    protected string $guard = 'sanctum';

    public function run(): void
    {
        $allPermissions = [
            // Bu seeder her izni developer/admin/user/manager rollerine verir,
            // ona görə yalnız alıcıya aid izinlər buradadır (admin-only
            // olanlar — məsələn `view orders-admin` — qəsdən yoxdur).
            'orders' => [
                'view orders',
                'basket order',
                'buy-one order',
                'completed-orders',
                'view-receipt',
                'download-receipt',
            ],
            'reviews' => [
                'view reviews',
                'add review',
            ],
            'theme' => [
                'view theme',
                'update theme',
            ],
            'chat' => [
                'full chat access',
            ],
            'admin' => [
                'has access',
            ],
            'popup' => [
                'add popup',
                'delete popup',
                'active popup',
            ],
        ];

        $developerRole = Role::firstOrCreate([
            'name' => 'developer',
            'guard_name' => $this->guard,
        ]);

        $adminRole = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => $this->guard,
        ]);
        $userRole = Role::firstOrCreate([
            'name' => 'user',
            'guard_name' => $this->guard,
        ]);
        $managerRole = Role::firstOrCreate([
            'name' => 'manager',
            'guard_name' => $this->guard,
        ]);

        foreach ($allPermissions as $group => $groupPermissions) {
            foreach ($groupPermissions as $permissionName) {
                $permission = Permission::firstOrCreate([
                    'name' => $permissionName,
                    'guard_name' => $this->guard,
                ]);

                $developerRole->givePermissionTo($permission);
                $adminRole->givePermissionTo($permission);
                $userRole->givePermissionTo($permission);
                $managerRole->givePermissionTo($permission);
            }
        }

        //        foreach ($allPermissions as $group => $groupPermissions) {
        //            foreach ($groupPermissions as $permissionName) {
        //
        //                $permission = Permission::where('name', $permissionName)
        //                    ->where('guard_name', $this->guard)
        //                    ->first();
        //
        //                if (!$permission) {
        //                    continue;
        //                }
        //
        //                $developerRole->givePermissionTo($permission);
        //                $adminRole->givePermissionTo($permission);
        //                $userRole->givePermissionTo($permission);
        //                $managerRole->givePermissionTo($permission);
        //            }
        //        }

        app('cache')->forget('spatie.permission.cache');
    }
}
