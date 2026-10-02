<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\RoleAndPermissions\Services\PermissionService;
use Modules\RoleAndPermissions\Services\RoleService;

class RoleController extends AdminController
{
    protected string $title = 'Rollar və icazələr';

    private string $guard = 'sanctum';

    private function roles(): RoleService
    {
        return app(RoleService::class);
    }

    public function index()
    {
        $this->requirePermission('manage-roles');

        return view('admin.pages.roles.index', [
            'title' => $this->title,
            'roles' => $this->roles()->rolesWithCounts($this->guard),
        ]);
    }

    public function create()
    {
        $this->requirePermission('manage-roles');

        return view('admin.pages.roles.form', [
            'title' => 'Rol əlavə et',
            'role' => null,
            'permissions' => app(PermissionService::class)->groupedByGuard($this->guard),
        ]);
    }

    public function edit(int $id)
    {
        $this->requirePermission('manage-roles');

        return view('admin.pages.roles.form', [
            'title' => 'Rolu redaktə et',
            'role' => $this->roles()->findWithPermissions($id, $this->guard),
            'permissions' => app(PermissionService::class)->groupedByGuard($this->guard),
        ]);
    }

    public function store(Request $request)
    {
        $this->requirePermission('manage-roles');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:190', Rule::unique('roles', 'name')->where('guard_name', $this->guard)],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,id'],
        ]);

        $this->roles()->createRole($data['name'], $this->guard, $data['permissions'] ?? []);

        return redirect()->route('admin.roles.index')->with('status', __('Rol yaradıldı.'));
    }

    public function update(Request $request, int $id)
    {
        $this->requirePermission('manage-roles');

        $role = $this->roles()->findWithPermissions($id, $this->guard);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:190', Rule::unique('roles', 'name')->where('guard_name', $this->guard)->ignore($role->id)],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,id'],
        ]);

        $this->roles()->updateRole($role, $data['name'], $data['permissions'] ?? []);

        return redirect()->route('admin.roles.index')->with('status', __('Rol yeniləndi.'));
    }

    public function destroy(int $id)
    {
        $this->requirePermission('manage-roles');

        $this->roles()->deleteRole($id, $this->guard);

        return back()->with('status', __('Rol silindi.'));
    }
}
