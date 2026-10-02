<?php

namespace Modules\RoleAndPermissions\Services;

use Spatie\Permission\Models\Permission;
use App\Models\User;

class PermissionService
{
    protected string $guard = 'sanctum';

    public function allPermissions()
    {
        $permissions = Permission::all();
        return responseHelper(__('Permissions retrieved successfully.'), 200, $permissions);
    }

    public function createPermission(string $name)
    {
        $permission = Permission::firstOrCreate([
            'name' => $name,
            'guard_name' => $this->guard,
        ]);
        return responseHelper(__('Permission created successfully.'), 201, $permission);
    }

    public function getPermission(string|int $permissionId)
    {
        $permission = Permission::find($permissionId);
        if (!$permission) {
            return responseHelper(__('Permission not found.'), 404);
        }
        return responseHelper(__('Permission details retrieved successfully.'), 200, $permission);
    }

    public function updatePermission(Permission|string $permission, string $name)
    {
        if (is_string($permission)) {
            $permission = Permission::where([
                'name' => $permission,
                'guard_name' => $this->guard,
            ])->first();
        }

        if (!$permission) {
            return responseHelper(__('Permission not found.'), 404);
        }

        $permission->name = $name;
        $permission->save();

        return responseHelper(__('Permission updated successfully.'), 200, $permission);
    }

    public function deletePermission(Permission|string $permission)
    {
        if (is_string($permission)) {
            $permission = Permission::where([
                'name' => $permission,
                'guard_name' => $this->guard,
            ])->first();
        }

        if ($permission) {
            $permission->delete();
        }

        return responseHelper(__('Permission deleted successfully.'), 200);
    }

    /** Permissions of a guard grouped by their last word (for the form). */
    public function groupedByGuard(string $guard = 'sanctum'): array
    {
        return \Spatie\Permission\Models\Permission::query()
            ->where('guard_name', $guard)
            ->orderBy('name')
            ->get()
            ->groupBy(function (\Spatie\Permission\Models\Permission $permission) {
                $parts = explode(' ', $permission->name);

                return end($parts);
            })
            ->all();
    }
}
