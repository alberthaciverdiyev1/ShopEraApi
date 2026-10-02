<?php

namespace Modules\Manager\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::guard('owner')->check() && Auth::guard('owner')->user()->hasRole('owner')) {
            return redirect()->route('manager.dashboard');
        }

        return view('manager::auth.login');
    }

    public function attempt(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::guard('owner')->attempt($credentials, $request->boolean('remember'))) {
            return back()->withInput($request->only('email'))
                ->withErrors(['email' => 'E-poçt və ya şifrə yanlışdır.']);
        }

        $request->session()->regenerate();

        if (! Auth::guard('owner')->user()->hasRole('owner')) {
            Auth::guard('owner')->logout();

            return back()->withErrors(['email' => 'Bu hesabın panelə girişi yoxdur.']);
        }

        return redirect()->intended(route('manager.dashboard'));
    }

    public function logout(Request $request)
    {
        Auth::guard('owner')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('manager.login');
    }
}
