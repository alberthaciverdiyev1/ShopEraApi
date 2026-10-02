<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Banner\Database\Seeders\BannerDatabaseSeeder;
use Modules\Blog\Database\Seeders\BlogDatabaseSeeder;
use Modules\Brand\Database\Seeders\BrandDatabaseSeeder;
use Modules\Category\Database\Seeders\CategoryDatabaseSeeder;
use Modules\Color\Database\Seeders\ColorDatabaseSeeder;
use Modules\Delivery\Database\Seeders\DeliveryDatabaseSeeder;
use Modules\Filter\Database\Seeders\FilterDatabaseSeeder;
use Modules\HelpAndPolicy\Database\Seeders\HelpAndPolicyDatabaseSeeder;
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

/**
 * A fresh tenant should be usable immediately after Manager provisioning:
 * core auth/settings plus a realistic storefront catalogue and marketing
 * content. Operational data such as orders, balances, chats and notifications
 * stay out of this baseline.
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

            // Demo storefront baseline for newly provisioned stores.
            UserDatabaseSeeder::class,
            CategoryDatabaseSeeder::class,
            BrandDatabaseSeeder::class,
            ColorDatabaseSeeder::class,
            SizeDatabaseSeeder::class,
            ProductDatabaseSeeder::class,
            ReviewDatabaseSeeder::class,
            BannerDatabaseSeeder::class,
            PromoCodeDatabaseSeeder::class,
            FilterDatabaseSeeder::class,
            BlogDatabaseSeeder::class,
            PopupDatabaseSeeder::class,
        ]);
    }
}
