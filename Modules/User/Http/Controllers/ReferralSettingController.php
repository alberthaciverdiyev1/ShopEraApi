<?php

namespace Modules\User\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\User\Services\ReferralSettingService;

class ReferralSettingController extends Controller
{
    private ReferralSettingService $service;

    public function __construct(ReferralSettingService $service)
    {
        $this->service = $service;
    }

    public function list()
    {
        return $this->service->list();
    }

    public function update(Request $request)
    {
        return $this->service->update($request);
    }
}
