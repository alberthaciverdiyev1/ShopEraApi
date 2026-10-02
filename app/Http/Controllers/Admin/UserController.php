<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Modules\User\Services\UserService;

class UserController extends AdminController
{
    protected string $title = 'İstifadəçilər';

    private string $guard = 'sanctum';

    private function service(): UserService
    {
        return app(UserService::class);
    }

    public function index(Request $request)
    {
        $this->requirePermission('view users');

        $rows = $this->service()->adminQuery($request)->paginate(20)->withQueryString();

        if ($this->isHtmx($request)) {
            return view('admin.pages.users._table', ['rows' => $rows]);
        }

        return view('admin.pages.users.index', [
            'title' => $this->title,
            'rows' => $rows,
            'roles' => $this->service()->roleNames($this->guard),
            'filters' => $request->only(['q', 'role']),
        ]);
    }

    public function show(int $id)
    {
        $this->requirePermission('view users');

        $details = $this->service()->adminDetails($id);
        $user = $details['user'];

        return view('admin.pages.users.show', array_merge($details, [
            'title' => trim($user->name.' '.$user->surname),
            'roles' => $this->service()->roleNames($this->guard),
        ]));
    }

    public function updateStatus(Request $request, int $id)
    {
        $this->requirePermission('update user');

        $this->service()->adminSetActive($id, $request->boolean('is_active'));

        return back()->with('status', __('İstifadəçi statusu yeniləndi.'));
    }

    public function updateRoles(Request $request, int $id)
    {
        $this->requirePermission('update user');

        $data = $request->validate([
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string', 'exists:roles,name'],
        ]);

        $this->service()->adminSyncRoles($id, $data['roles'] ?? []);

        return back()->with('status', __('Rollar yeniləndi.'));
    }

    public function changePassword(Request $request, int $id)
    {
        $this->requirePermission('update user');

        $data = $request->validate([
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $this->service()->adminChangePassword($id, $data['password']);

        return back()->with('status', __('Şifrə yeniləndi.'));
    }

    public function destroy(int $id)
    {
        $this->requirePermission('destroy user');

        $this->service()->adminDelete($id);

        return redirect()->route('admin.users.index')->with('status', __('İstifadəçi silindi.'));
    }
}
