<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\LoginRequest;
use App\Http\Requests\Web\RegisterRequest;
use App\Support\Concerns\InteractsWithModuleControllers;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\User\Http\Controllers\SessionAuthController;

class AuthController extends Controller
{
    use InteractsWithModuleControllers;

    public function showLogin(): View
    {
        app()->setLocale('az');

        return view('storefront.auth.login', [
            'categories' => $this->storefrontCategories(),
        ]);
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        app(SessionAuthController::class)->login(
            $this->moduleHttpRequest($request->validated(), 'POST')
        );

        return redirect()->intended(route('storefront.home'))
            ->with('status', 'Xoş gəlmisiniz!');
    }

    public function showRegister(): View
    {
        app()->setLocale('az');

        return view('storefront.auth.register', [
            'categories' => $this->storefrontCategories(),
        ]);
    }

    public function register(RegisterRequest $request): RedirectResponse
    {
        app(SessionAuthController::class)->register(
            $this->moduleHttpRequest($request->validated(), 'POST')
        );

        return redirect()->route('storefront.home')
            ->with('status', 'Hesabınız uğurla yaradıldı.');
    }

    public function logout(): RedirectResponse
    {
        app(SessionAuthController::class)->logout();

        return redirect()->route('storefront.home');
    }
}
