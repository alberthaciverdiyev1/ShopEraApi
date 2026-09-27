<?php

namespace Modules\User\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\User\Services\AppleAuthService;

class AppleController extends Controller
{
    public function __construct(private readonly AppleAuthService $service)
    {
    }

    public function loginWithToken(Request $request)
    {
        return $this->service->loginWithToken($request);
    }
}
