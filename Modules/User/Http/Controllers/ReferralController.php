<?php

namespace Modules\User\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\User\Services\ReferralService;

class ReferralController extends Controller
{
    private ReferralService $service;

    public function __construct(ReferralService $service)
    {
        $this->service = $service;
    }

    public function getAllUsersReferralDetails(Request $request)
    {
        return $this->service->getAllUsersReferralDetails($request);
    }

    public function getReferredUsers()
    {
        $id = auth()->id();

        return $this->service->getReferredUsers($id);
    }
}
