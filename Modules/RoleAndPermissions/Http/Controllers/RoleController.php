<?php

namespace Modules\RoleAndPermissions\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\RoleAndPermissions\Services\RoleService;
use Modules\User\Http\Entities\User;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    protected $roleService;

    public function __construct(RoleService $roleService)
    {
        $this->roleService = $roleService;

        $this->middleware('permission:manage-roles');
    }

    public function getAll(Request $request)
    {
        return $this->roleService->getAll($request);
    }

    public function add(Request $request)
    {
        $request->validate(['name' => 'required|string|max:255']);
        return $this->roleService->add($request->name);
    }

    public function details($id)
    {
        return $this->roleService->details($id);
    }

    public function update(Request $request, Role $role)
    {
        $request->validate(['name' => 'required|string|max:255']);
        return $this->roleService->update($role, $request->name);
    }

    public function delete(Role $role, RoleService $roleService)
    {
        return $roleService->delete($role->id);
    }


    public function assignRoleToUser(Request $request, $userId)
    {
        $request->validate(['role' => 'required|string']);
        $user = User::findOrFail($userId);

        return $this->roleService->giveRoleToUser($user, $request->role);
    }

    public function revokeRoleFromUser(Request $request, $userId)
    {
        $request->validate(['role' => 'required|string']);
        $user = User::findOrFail($userId);

        return $this->roleService->revokeRoleFromUser($user, $request->role);
    }
    public function givePermission(Request $request, Role $role)
    {
        $request->validate(['permission' => 'required|string']);
        return $this->roleService->givePermission($role, $request->permission);
    }

    public function revokePermission(Request $request, Role $role)
    {
        $request->validate(['permission' => 'required|string']);
        return $this->roleService->revokePermission($role, $request->permission);
    }

    public function getUsersWithRole()
    {
        return $this->roleService->getUsersWithRole();

    }
}
