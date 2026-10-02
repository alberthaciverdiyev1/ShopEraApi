<?php

namespace Modules\RoleAndPermissions\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Admin panel routes-i `permission:*` middleware istifadə edir, lakin bu
 * icazələr heç bir seeder-də yaradılmırdı — nəticədə heç kim (admin daxil)
 * həmin endpoint-lərə girə bilmirdi (403). Bu seeder panelin ehtiyac duyduğu
 * bütün icazələri yaradır və admin/developer/manager rollarına verir.
 */
class AdminPermissionSeeder extends Seeder
{
    protected string $guard = 'sanctum';

    public function run(): void
    {
        $permissions = [
            // məhsul
            'view products', 'add product', 'details product', 'details-admin product',
            'update product', 'delete product', 'statistics product',
            // kateqoriya
            'view categories', 'view categories-with-products', 'add category',
            'details category', 'update category', 'delete category',
            // brend
            'view brands', 'add brand', 'details brand', 'update brand', 'delete brand',
            // rəng
            'view colors', 'add color', 'details color', 'update color', 'delete color',
            // ölçü
            'view sizes', 'add size', 'details size', 'update size', 'delete size',
            // çatdırılma
            'view deliveries', 'add delivery', 'details delivery', 'update delivery', 'delete delivery',
            // faq / hüquqi
            'view helpandpolicys', 'create helpandpolicy', 'store helpandpolicy',
            'edit helpandpolicy', 'update legal-terms', 'destroy helpandpolicy',
            // banner
            'view banners', 'add banner', 'delete banner',
            // promo-kod
            'view promo-codes', 'add promo-code', 'details promo-code',
            'update promo-code', 'delete promo-code',
            // filter
            'add filter', 'update filter', 'delete filter',
            // bildiriş
            'view notifications', 'send notification',
            // sifariş
            'view orders', 'view orders-admin', 'update order', 'delete order',
            // balans
            'view balances', 'deposit balance', 'withdraw balance',
            'get-balance balance', 'history balance',
            // istifadəçi
            'view users', 'create user', 'edit user', 'update user', 'destroy user',
            // parametr
            'view setting', 'update setting',
            // rol & icazə
            'manage-roles', 'manage-permissions',
        ];

        $roles = ['admin', 'developer', 'manager'];

        foreach ($roles as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => $this->guard]);
        }

        foreach ($permissions as $name) {
            $permission = Permission::firstOrCreate([
                'name' => $name,
                'guard_name' => $this->guard,
            ]);

            foreach ($roles as $roleName) {
                Role::findByName($roleName, $this->guard)->givePermissionTo($permission);
            }
        }
    }
}
