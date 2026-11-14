<?php

namespace App\Http\Controllers\Web;

use App\Core\Concerns\InteractsWithModuleControllers;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\LoginRequest;
use App\Http\Requests\Web\RegisterRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\Setting\Http\Controllers\SettingController;
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

    /**
     * Password recovery is handled by our team over WhatsApp instead of an
     * automated email link.
     */
    public function showForgotPassword(): View
    {
        app()->setLocale('az');

        $settings = (array) $this->payload(
            app(SettingController::class)->list($this->moduleHttpRequest())
        );
        $settings = (array) ($settings[0] ?? $settings);

        return view('storefront.auth.forgot-password', [
            'categories' => $this->storefrontCategories(),
            'whatsapp' => $settings['whatsapp_number'] ?? null,
            'supportEmail' => $settings['email'] ?? null,
        ]);
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
