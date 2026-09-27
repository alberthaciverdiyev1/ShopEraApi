<?php

namespace Database\Seeders;

use App\Models\User;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Modules\Banner\Database\Seeders\BannerDatabaseSeeder;
use Modules\Brand\Database\Seeders\BrandDatabaseSeeder;
use Modules\Category\Database\Seeders\CategoryDatabaseSeeder;
use Modules\Color\Database\Seeders\ColorDatabaseSeeder;
use Modules\Delivery\Database\Seeders\DeliveryDatabaseSeeder;
use Modules\HelpAndPolicy\Database\Seeders\HelpAndPolicyDatabaseSeeder;
use Modules\Notification\Database\Seeders\NotificationDatabaseSeeder;
use Modules\Product\Database\Seeders\ProductDatabaseSeeder;
use Modules\PromoCode\Database\Seeders\PromoCodeDatabaseSeeder;
use Modules\RoleAndPermissions\Database\Seeders\PermissionDatabaseSeeder;
use Modules\RoleAndPermissions\Database\Seeders\RoleDatabaseSeeder;
use Modules\Setting\Database\Seeders\SettingDatabaseSeeder;
use Modules\Size\Database\Seeders\SizeDatabaseSeeder;
use Modules\User\Database\Seeders\UserDatabaseSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();
        $this->call([
//            SettingDatabaseSeeder::class,
           // CategoryDatabaseSeeder::class,
           // BrandDatabaseSeeder::class,
           // ColorDatabaseSeeder::class,
           // SizeDatabaseSeeder::class,
//            RoleDatabaseSeeder::class,
            PermissionDatabaseSeeder::class,
//            UserDatabaseSeeder::class,
            //ProductDatabaseSeeder::class,
          //  NotificationDatabaseSeeder::class,
//            DeliveryDatabaseSeeder::class,
//            HelpAndPolicyDatabaseSeeder::class,
//            BannerDatabaseSeeder::class
        //    PromoCodeDatabaseSeeder::class,
        ]);
    }
}
