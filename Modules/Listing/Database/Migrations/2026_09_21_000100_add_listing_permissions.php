<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The permissions the classifieds admin screens are gated on, handed to the
 * roles that already run the shop.
 *
 * Granting them here rather than leaving it to a person is deliberate: a fresh
 * permission nobody holds turns the new screens into a wall of 403s, and the
 * cause is not obvious from the panel.
 */
return new class extends Migration
{
    private const PERMISSIONS = ['view listings', 'manage listings'];

    public function up(): void
    {
        $guard = DB::table('permissions')->value('guard_name') ?: 'sanctum';

        $permissionIds = [];

        foreach (self::PERMISSIONS as $name) {
            $existing = DB::table('permissions')
                ->where('name', $name)
                ->where('guard_name', $guard)
                ->value('id');

            $permissionIds[] = $existing ?: DB::table('permissions')->insertGetId([
                'name' => $name,
                'guard_name' => $guard,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Whoever already runs live sales is who will moderate ads, so the
        // grant follows that role set instead of a hard-coded role name.
        $roleIds = DB::table('roles')
            ->where('name', 'admin')
            ->pluck('id')
            ->merge(
                DB::table('role_has_permissions')
                    ->join('permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
                    ->where('permissions.name', 'manage live')
                    ->pluck('role_has_permissions.role_id')
            )
            ->unique();

        foreach ($roleIds as $roleId) {
            foreach ($permissionIds as $permissionId) {
                DB::table('role_has_permissions')->insertOrIgnore([
                    'role_id' => $roleId,
                    'permission_id' => $permissionId,
                ]);
            }
        }

        app()['cache']->forget('spatie.permission.cache');
    }

    public function down(): void
    {
        $ids = DB::table('permissions')->whereIn('name', self::PERMISSIONS)->pluck('id');

        DB::table('role_has_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();

        app()['cache']->forget('spatie.permission.cache');
    }
};
