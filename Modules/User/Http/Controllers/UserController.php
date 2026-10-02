<?php

namespace Modules\User\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\User\Services\UserService;

/**
 * @property UserService $service
 */
class UserController extends Controller
{
    private UserService $service;

    public function __construct(UserService $service)
    {
        $this->middleware('permission:view users')->only('getAll');
        // Reading your own profile is free; reading someone else's needs the
        // `details user` permission (enforced in the service).
        // A user may always edit their own profile; editing somebody else's
        // still needs `update user` (checked in the service).
        $this->middleware('permission:update user')->only('changeWholesalerStatus');

        $this->service = $service;
    }

    public function changeEmail(Request $request): JsonResponse
    {
        return $this->service->changeEmail($request);
    }

    public function changeName(Request $request): JsonResponse
    {
        return $this->service->changeName($request);
    }

    public function changeSurname(Request $request): JsonResponse
    {
        return $this->service->changeSurname($request);
    }

    public function changePhone(Request $request): JsonResponse
    {
        return $this->service->changePhone($request);
    }

    public function changeAvatar(Request $request): JsonResponse
    {
        return $this->service->changeAvatar($request);
    }

    public function removeAvatar(Request $request): JsonResponse
    {
        return $this->service->removeAvatar($request);
    }

    public function getAll(Request $request): JsonResponse
    {
        return $this->service->getAll($request);
    }

    public function blockUser(Request $request): JsonResponse
    {
        return $this->service->blockUser($request);
    }

    public function changeWholesalerStatus(Request $request): JsonResponse
    {
        return $this->service->changeWholesalerStatus($request);
    }

    public function delete(?int $id = null): JsonResponse
    {
        return $this->service->delete($id);
    }

    public function details(?int $id = null): JsonResponse
    {
        return $this->service->details($id);
    }

    public function deleteMyAccount(): JsonResponse
    {
        return $this->service->deleteMyAccount();
    }

    public function deleteMyAccountHtml()
    {
        return $this->service->deleteMyAccountHtml();
    }
}
