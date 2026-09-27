<?php

namespace Modules\User\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Modules\User\Http\Entities\User;
use Modules\User\Services\GoogleAuthService;

class GoogleController extends Controller
{
    private GoogleAuthService $service;

    function __construct(GoogleAuthService $service)
    {
        $this->service = $service;
    }

    public function redirectToGoogle()
    {
        return $this->service->redirectToGoogle();
    }

    public function handleGoogleCallback()
    {
        return $this->service->handleGoogleCallback();
    }

    public function loginWithToken(Request $request)
    {
        return $this->service->loginWithToken($request);
    }
}
