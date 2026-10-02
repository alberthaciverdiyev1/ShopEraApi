<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends AdminController
{
    protected string $title = 'Rollar və icazələr';

    private string $guard = 'sanctum';

    public function index()
    {
        $this->requirePermission('manage-roles');

        $roles = Role::query()
            ->where('guard_name', $this->guard)
            ->withCount(['permissions', 'users'])
            ->orderBy('id')
            ->get();

        return view('admin.pages.roles.index', [
            'title' => $this->title,
            'roles' => $roles,
        ]);
    }

    public function create()
    {
        $this->requirePermission('manage-roles');

        return view('admin.pages.roles.form', [
            'title' => 'Rol əlavə et',
            'role' => null,
            'permissions' => $this->permissionGroups(),
        ]);
    }

    public function edit(int $id)
    {
        $this->requirePermission('manage-roles');

        $role = Role::query()->where('guard_name', $this->guard)->with('permissions')->findOrFail($id);

        return view('admin.pages.roles.form', [
            'title' => 'Rolu redaktə et',
            'role' => $role,
            'permissions' => $this->permissionGroups(),
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

        $role = Role::create(['name' => $data['name'], 'guard_name' => $this->guard]);
        $role->syncPermissions($data['permissions'] ?? []);

        return redirect()->route('admin.roles.index')->with('status', __('Rol yaradıldı.'));
    }

    public function update(Request $request, int $id)
    {
        $this->requirePermission('manage-roles');

        $role = Role::query()->where('guard_name', $this->guard)->findOrFail($id);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:190', Rule::unique('roles', 'name')->where('guard_name', $this->guard)->ignore($role->id)],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,id'],
        ]);

        $role->update(['name' => $data['name']]);
        $role->syncPermissions($data['permissions'] ?? []);

        return redirect()->route('admin.roles.index')->with('status', __('Rol yeniləndi.'));
    }

    public function destroy(int $id)
    {
        $this->requirePermission('manage-roles');

        $role = Role::query()->where('guard_name', $this->guard)->findOrFail($id);

        abort_if(in_array($role->name, ['admin', 'developer', 'manager'], true), 403, 'Bu rol silinə bilməz.');

        $role->delete();

        return back()->with('status', __('Rol silindi.'));
    }

    private function permissionGroups(): array
    {
        return Permission::query()
            ->where('guard_name', $this->guard)
            ->orderBy('name')
            ->get()
            ->groupBy(function (Permission $permission) {
                $parts = explode(' ', $permission->name);

                return end($parts);
            })
            ->all();
    }
}
