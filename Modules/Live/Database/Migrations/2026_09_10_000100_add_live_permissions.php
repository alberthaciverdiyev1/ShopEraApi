<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Adds the two permissions the live screens are gated on and hands them to the
 * roles that already run the shop.
 *
 * Granting them here rather than leaving it to a person is deliberate: a fresh
 * permission nobody holds turns the new admin screens into a wall of 403s the
 * first time they are opened, and the cause is not obvious from the panel.
 */
return new class extends Migration
{
    private const PERMISSIONS = ['view live', 'manage live'];

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

        // Whoever may already broadcast a notification to every customer is the
        // same person who runs a live sale, so the grant follows that role set
        // instead of hard-coding a single role name.
        $roleIds = DB::table('roles')
            ->where('name', 'admin')
            ->pluck('id')
            ->merge(
                DB::table('role_has_permissions')
                    ->join('permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
                    ->where('permissions.name', 'send notification')
                    ->pluck('role_has_permissions.role_id')
            )
            ->unique();

        foreach ($roleIds as $roleId) {
            foreach ($permissionIds as $permissionId) {
                $alreadyGranted = DB::table('role_has_permissions')
                    ->where('role_id', $roleId)
                    ->where('permission_id', $permissionId)
                    ->exists();

                if (! $alreadyGranted) {
                    DB::table('role_has_permissions')->insert([
                        'role_id' => $roleId,
                        'permission_id' => $permissionId,
                    ]);
                }
            }
        }

        app()->bound(\Spatie\Permission\PermissionRegistrar::class)
            && app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $ids = DB::table('permissions')->whereIn('name', self::PERMISSIONS)->pluck('id');

        DB::table('role_has_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();

        app()->bound(\Spatie\Permission\PermissionRegistrar::class)
            && app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
