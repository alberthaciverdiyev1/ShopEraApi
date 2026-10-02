<?php

namespace Modules\User\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\User\Services\AuthService;
use Nwidart\Modules\Facades\Module;

/**
 * @group Auth Management
 */
class AuthController extends Controller
{
    private AuthService $service;

    public function __construct(AuthService $service)
    {
        $this->service = $service;
        $this->middleware('permission:view users')->only('passwordResetRequests');
        $this->middleware('permission:update user')->only(['resolvePasswordResetRequest', 'dismissPasswordResetRequest']);

        //        if (Module::find('Roles')->isEnabled()) {
        //            $this->middleware('permission:view users')->only('index');
        //            $this->middleware('permission:create user')->only('create');
        //            $this->middleware('permission:store user')->only('store');
        //            $this->middleware('permission:edit user')->only('edit');
        //            $this->middleware('permission:update user')->only('update');
        //            $this->middleware('permission:destroy user')->only('destroy');
        //        }
    }

    public function register(Request $request)
    {
        return $this->service->register($request);
    }

    public function sendOtp(Request $request)
    {
        return $this->service->sendOtp($request);
    }

    public function checkOtp(Request $request)
    {
        return $this->service->checkOtp($request);
    }

    public function login(Request $request)
    {
        return $this->service->login($request);
    }

    public function logout(Request $request)
    {
        return $this->service->logout($request);
    }

    public function resetPassword(Request $request)
    {
        return $this->service->resetPassword($request);
    }

    /** Sends the reset code to an e-mail address. */
    public function sendPasswordResetEmail(Request $request)
    {
        return $this->service->sendPasswordResetEmail($request);
    }

    /** Sets the new password against the e-mailed code. */
    public function resetPasswordByEmail(Request $request)
    {
        return $this->service->resetPasswordByEmail($request);
    }

    public function changePassword(Request $request)
    {
        return $this->service->changePassword($request);
    }

    public function adminChangePassword(Request $request)
    {
        return $this->service->adminChangePassword($request);
    }

    public function createPasswordResetRequest(Request $request)
    {
        return $this->service->createPasswordResetRequest($request);
    }

    public function passwordResetRequests(Request $request)
    {
        return $this->service->passwordResetRequests($request);
    }

    public function resolvePasswordResetRequest(Request $request, int $id)
    {
        return $this->service->resolvePasswordResetRequest($request, $id);
    }

    public function dismissPasswordResetRequest(Request $request, int $id)
    {
        return $this->service->dismissPasswordResetRequest($request, $id);
    }
}
