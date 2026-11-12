<?php

namespace Modules\User\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\User\Services\SessionAuthService;

/**
 * Session-guard authentication used by the Blade storefront. The mobile API
 * keeps using AuthController with Sanctum tokens.
 */
class SessionAuthController extends Controller
{
    public function __construct(private readonly SessionAuthService $service) {}

    public function login(Request $request): JsonResponse
    {
        $user = $this->service->attempt(
            (string) $request->input('identifier'),
            (string) $request->input('password'),
            (bool) $request->input('remember', false),
        );

        return responseHelper('OK', 200, ['id' => $user->id]);
    }

    public function register(Request $request): JsonResponse
    {
        $user = $this->service->register($request->all());

        return responseHelper('OK', 200, ['id' => $user->id]);
    }

    public function logout(): JsonResponse
    {
        $this->service->logout();

        return responseHelper('OK', 200);
    }
}
