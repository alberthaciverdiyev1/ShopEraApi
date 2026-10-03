<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\PhoneHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\Manager\Entities\OwnerActivity;
use Modules\Manager\Entities\SiteOwner;
use Modules\User\Entities\User;

class AuthController extends AdminController
{
    public function showLogin()
    {
        if (Auth::guard('admin')->check()) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'identifier' => ['required', 'string', 'max:190'],
            'password' => ['required', 'string'],
        ]);

        $identifier = trim($credentials['identifier']);
        $user = null;

        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            $user = User::query()->whereRaw('LOWER(email) = ?', [Str::lower($identifier)])->first();
        }

        if (! $user) {
            $phone = PhoneHelper::normalize($identifier);
            if ($phone !== '') {
                $user = PhoneHelper::wherePhone(User::query(), $phone)->first();
            }
        }

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return back()
                ->withInput($request->only('identifier'))
                ->withErrors(['identifier' => __('Telefon/e-poçt və ya şifrə yanlışdır.')]);
        }

        if (! $user->is_active) {
            return back()
                ->withInput($request->only('identifier'))
                ->withErrors(['identifier' => __('Bu hesab bloklanıb.')]);
        }

        if (! admin_has_role($user) && $user->getAllPermissions()->isEmpty()) {
            return back()
                ->withInput($request->only('identifier'))
                ->withErrors(['identifier' => __('Bu hesabın idarə panelinə girişi yoxdur.')]);
        }

        Auth::guard('admin')->login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        $owner = SiteOwner::findForCurrentTenant();
        if ($owner) {
            $owner->forceFill(['last_login_at' => now(), 'last_seen_at' => now()])->save();
            OwnerActivity::record($owner, 'login', __('Panelə daxil oldu'), $request->ip());
        }

        return redirect()->intended(route('admin.dashboard'));
    }

    public function logout(Request $request)
    {
        $owner = SiteOwner::findForCurrentTenant();
        if ($owner) {
            $owner->forceFill(['last_logout_at' => now()])->save();
            OwnerActivity::record($owner, 'logout', __('Paneldən çıxdı'), $request->ip());
        }

        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
