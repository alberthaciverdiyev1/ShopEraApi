<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Delivery\Database\Seeders\DeliveryDatabaseSeeder;
use Modules\HelpAndPolicy\Database\Seeders\HelpAndPolicyDatabaseSeeder;
use Modules\RoleAndPermissions\Database\Seeders\PermissionDatabaseSeeder;
use Modules\RoleAndPermissions\Database\Seeders\RoleDatabaseSeeder;
use Modules\Setting\Database\Seeders\SettingDatabaseSeeder;
use Modules\Setting\Database\Seeders\ThemeColorDatabaseSeeder;

/**
 * The minimum a fresh tenant database needs to work: roles/permissions
 * (a signed-up shopper gets the `user` role), settings, theme and the legal
 * pages. No demo catalogue.
 */
class TenantDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleDatabaseSeeder::class,
            PermissionDatabaseSeeder::class,
            SettingDatabaseSeeder::class,
            ThemeColorDatabaseSeeder::class,
            HelpAndPolicyDatabaseSeeder::class,
            DeliveryDatabaseSeeder::class,
        ]);
    }
}
