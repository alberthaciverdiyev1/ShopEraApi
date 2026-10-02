<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Modules\User\Entities\User;
use Spatie\Permission\Models\Role;

class UserController extends AdminController
{
    protected string $title = 'İstifadəçilər';

    private string $guard = 'sanctum';

    public function index(Request $request)
    {
        $this->requirePermission('view users');

        $query = User::query()->with('roles')->latest('id');

        if (($term = trim((string) $request->query('q', ''))) !== '') {
            $query->where(function ($inner) use ($term) {
                $inner->where('name', 'like', "%{$term}%")
                    ->orWhere('surname', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%");
            });
        }

        if ($request->filled('role')) {
            $query->whereHas('roles', fn ($q) => $q->where('name', $request->query('role')));
        }

        $rows = $query->paginate(20)->withQueryString();

        if ($this->isHtmx($request)) {
            return view('admin.pages.users._table', ['rows' => $rows]);
        }

        return view('admin.pages.users.index', [
            'title' => $this->title,
            'rows' => $rows,
            'roles' => Role::query()->where('guard_name', $this->guard)->orderBy('name')->pluck('name'),
            'filters' => $request->only(['q', 'role']),
        ]);
    }

    public function show(int $id)
    {
        $this->requirePermission('view users');

        $user = User::query()
            ->with(['roles', 'permissions', 'balance'])
            ->findOrFail($id);

        return view('admin.pages.users.show', [
            'title' => trim($user->name.' '.$user->surname),
            'user' => $user,
            'roles' => Role::query()->where('guard_name', $this->guard)->orderBy('name')->pluck('name'),
            'recentOrders' => \Modules\Order\Entities\Order::query()->where('user_id', $user->id)->latest('id')->limit(10)->get(),
            'ordersCount' => \Modules\Order\Entities\Order::query()->where('user_id', $user->id)->count(),
        ]);
    }

    public function updateStatus(Request $request, int $id)
    {
        $this->requirePermission('update user');

        $user = User::query()->findOrFail($id);

        abort_if($user->id === admin_user()?->id, 403, 'Öz hesabınızı bloklaya bilməzsiniz.');

        $user->update(['is_active' => $request->boolean('is_active')]);

        return back()->with('status', __('İstifadəçi statusu yeniləndi.'));
    }


    public function updateRoles(Request $request, int $id)
    {
        $this->requirePermission('update user');

        $data = $request->validate([
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string', 'exists:roles,name'],
        ]);

        $user = User::query()->findOrFail($id);
        $user->syncRoles($data['roles'] ?? []);

        return back()->with('status', __('Rollar yeniləndi.'));
    }

    public function changePassword(Request $request, int $id)
    {
        $this->requirePermission('update user');

        $data = $request->validate([
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        User::query()->findOrFail($id)->update(['password' => Hash::make($data['password'])]);

        return back()->with('status', __('Şifrə yeniləndi.'));
    }

    public function destroy(int $id)
    {
        $this->requirePermission('destroy user');

        $user = User::query()->findOrFail($id);

        abort_if($user->id === admin_user()?->id, 403, 'Öz hesabınızı silə bilməzsiniz.');

        $user->delete();

        return redirect()->route('admin.users.index')->with('status', __('İstifadəçi silindi.'));
    }
}
