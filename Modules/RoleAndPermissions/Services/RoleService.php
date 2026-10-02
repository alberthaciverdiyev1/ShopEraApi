<?php

namespace Modules\RoleAndPermissions\Services;

use Exception;
use Modules\User\Entities\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleService
{
    protected string $guard = 'sanctum';

    public function getAll($request)
    {
        $includePermissions = $request->boolean('permission', false);

        $rolesQuery = Role::query();

        if ($includePermissions) {
            $rolesQuery->with('permissions');
        }

        $roles = $rolesQuery->get();

        return responseHelper(__('Roles retrieved successfully.'), 200, $roles);
    }

    public function add(string $name)
    {
        $role = Role::create([
            'name' => $name,
            'guard_name' => $this->guard,
        ]);

        return responseHelper(__('Role created successfully.'), 201, $role);
    }

    public function details(string|int $roleId)
    {
        $role = Role::with('permissions')->find($roleId);
        if (! $role) {
            return responseHelper(__('Role not found.'), 404);
        }

        return responseHelper(__('Role details retrieved successfully.'), 200, $role);
    }

    public function update(Role $role, string $name)
    {
        $role->name = $name;
        $role->save();

        return responseHelper(__('Role updated successfully.'), 200, $role);
    }

    public function delete(int $roleId): array
    {
        $role = Role::find($roleId);

        if (! $role) {
            return [
                'success' => false,
                'status_code' => 404,
                'message' => 'Role not found.',
                'data' => null,
            ];
        }

        try {
            $role->permissions()->detach();

            $role->delete();

            return [
                'success' => true,
                'status_code' => 200,
                'message' => 'Role deleted successfully.',
                'data' => null,
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'status_code' => 500,
                'message' => 'Failed to delete role: '.$e->getMessage(),
                'data' => null,
            ];
        }
    }

    public function givePermission(Role $role, string|Permission $permission)
    {
        if (is_string($permission)) {
            $permission = Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => $this->guard,
            ]);
        }

        $role->givePermissionTo($permission);

        return responseHelper(__('Permission assigned to role successfully.'), 200, $role->permissions);
    }

    public function revokePermission(Role $role, string|Permission $permission)
    {
        if (is_string($permission)) {
            $permission = Permission::where([
                'name' => $permission,
                'guard_name' => $this->guard,
            ])->first();
        }

        if ($permission) {
            $role->revokePermissionTo($permission);
        }

        return responseHelper(__('Permission revoked from role successfully.'), 200, $role->permissions);
    }

    public function giveRoleToUser(User $user, string|Role $role)
    {
        if (is_string($role)) {
            $role = Role::where([
                'name' => $role,
                'guard_name' => $this->guard,
            ])->first();
        }

        if ($role) {
            $user->assignRole($role);
        }

        return responseHelper(__('Role assigned to user successfully.'), 200, $user->roles);
    }

    public function revokeRoleFromUser(User $user, string|Role $role)
    {
        if (is_string($role)) {
            $role = Role::where([
                'name' => $role,
                'guard_name' => $this->guard,
            ])->first();
        }

        if ($role) {
            $user->removeRole($role);
        }

        return responseHelper(__('Role removed from user successfully.'), 200, $user->roles);
    }

    public function getUsersWithRole()
    {
        return User::with('roles')->get();
    }

    /** Roles of a guard with counts, for the admin index. */
    public function rolesWithCounts(string $guard = 'sanctum')
    {
        return \Spatie\Permission\Models\Role::query()
            ->where('guard_name', $guard)
            ->withCount(['permissions', 'users'])
            ->orderBy('id')
            ->get();
    }

    public function findWithPermissions(int $id, string $guard = 'sanctum')
    {
        return \Spatie\Permission\Models\Role::query()
            ->where('guard_name', $guard)
            ->with('permissions')
            ->findOrFail($id);
    }

    public function createRole(string $name, string $guard = 'sanctum', array $permissions = [])
    {
        $role = \Spatie\Permission\Models\Role::create(['name' => $name, 'guard_name' => $guard]);
        $role->syncPermissions($permissions);

        return $role;
    }

    public function updateRole(\Spatie\Permission\Models\Role $role, string $name, array $permissions = []): void
    {
        $role->update(['name' => $name]);
        $role->syncPermissions($permissions);
    }

    public function deleteRole(int $id, string $guard = 'sanctum'): void
    {
        $role = $this->findWithPermissions($id, $guard);

        abort_if(in_array($role->name, ['admin', 'developer', 'manager'], true), 403, 'Bu rol silinə bilməz.');

        $role->delete();
    }
}
