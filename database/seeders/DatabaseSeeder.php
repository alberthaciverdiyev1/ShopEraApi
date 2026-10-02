<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Balance\Database\Seeders\BalanceDatabaseSeeder;
use Modules\Banner\Database\Seeders\BannerDatabaseSeeder;
use Modules\Brand\Database\Seeders\BrandDatabaseSeeder;
use Modules\Category\Database\Seeders\CategoryDatabaseSeeder;
use Modules\Chat\Database\Seeders\ChatDatabaseSeeder;
use Modules\Color\Database\Seeders\ColorDatabaseSeeder;
use Modules\Delivery\Database\Seeders\DeliveryDatabaseSeeder;
use Modules\Filter\Database\Seeders\FilterDatabaseSeeder;
use Modules\HelpAndPolicy\Database\Seeders\HelpAndPolicyDatabaseSeeder;
use Modules\Notification\Database\Seeders\NotificationDatabaseSeeder;
use Modules\Order\Database\Seeders\OrderDatabaseSeeder;
use Modules\Popup\Database\Seeders\PopupDatabaseSeeder;
use Modules\Product\Database\Seeders\ProductDatabaseSeeder;
use Modules\Product\Database\Seeders\ReviewDatabaseSeeder;
use Modules\PromoCode\Database\Seeders\PromoCodeDatabaseSeeder;
use Modules\RoleAndPermissions\Database\Seeders\PermissionDatabaseSeeder;
use Modules\RoleAndPermissions\Database\Seeders\RoleDatabaseSeeder;
use Modules\Setting\Database\Seeders\SettingDatabaseSeeder;
use Modules\Setting\Database\Seeders\ThemeColorDatabaseSeeder;
use Modules\Size\Database\Seeders\SizeDatabaseSeeder;
use Modules\User\Database\Seeders\UserDatabaseSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            // Roles and permissions first: user/product seeders depend on them.
            RoleDatabaseSeeder::class,
            PermissionDatabaseSeeder::class,
            SettingDatabaseSeeder::class,
            ThemeColorDatabaseSeeder::class,

            // Accounts first: the product factory picks a random user.
            UserDatabaseSeeder::class,

            // Catalog building blocks.
            CategoryDatabaseSeeder::class,
            BrandDatabaseSeeder::class,
            ColorDatabaseSeeder::class,
            SizeDatabaseSeeder::class,
            ProductDatabaseSeeder::class,
            ReviewDatabaseSeeder::class,

            // Storefront content and operations.
            DeliveryDatabaseSeeder::class,
            BannerDatabaseSeeder::class,
            NotificationDatabaseSeeder::class,
            PromoCodeDatabaseSeeder::class,
            FilterDatabaseSeeder::class,
            HelpAndPolicyDatabaseSeeder::class,
            ChatDatabaseSeeder::class,

            // Orders before balances: balance movements reference the orders.
            OrderDatabaseSeeder::class,
            BalanceDatabaseSeeder::class,
            PopupDatabaseSeeder::class,
        ]);
    }
}
