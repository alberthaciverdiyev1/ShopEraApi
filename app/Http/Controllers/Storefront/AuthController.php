<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\LoginRequest;
use App\Http\Requests\Storefront\RegisterRequest;
use App\Services\Storefront\CatalogService;
use App\Services\Storefront\SessionAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function __construct(
        private readonly SessionAuthService $auth,
        private readonly CatalogService $catalog,
    ) {}

    public function showLogin(): View
    {
        app()->setLocale('az');

        return view('storefront.auth.login', [
            'categories' => $this->catalog->categories(),
        ]);
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $this->auth->attempt(
            $request->string('identifier')->toString(),
            $request->string('password')->toString(),
            $request->boolean('remember'),
        );

        return redirect()->intended(route('storefront.home'))
            ->with('status', 'Xoş gəlmisiniz!');
    }

    public function showRegister(): View
    {
        app()->setLocale('az');

        return view('storefront.auth.register', [
            'categories' => $this->catalog->categories(),
        ]);
    }

    public function register(RegisterRequest $request): RedirectResponse
    {
        $this->auth->register($request->validated());

        return redirect()->route('storefront.home')
            ->with('status', 'Hesabınız uğurla yaradıldı.');
    }

    public function logout(Request $request): RedirectResponse
    {
        $this->auth->logout();

        return redirect()->route('storefront.home');
    }
}
