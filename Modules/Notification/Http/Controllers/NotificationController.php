<?php

namespace Modules\Notification\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Notification\Services\NotificationService;

class NotificationController extends Controller
{
    private NotificationService $service;

    function __construct(NotificationService $service)
    {
        $this->middleware('permission:view notifications')->only('getAll');
        $this->service = $service;
    }

    public function getAll(Request $request)
    {
        return $this->service->list($request);
    }
    public function getAllAdmin(Request $request)
    {
        return $this->service->listAdmin($request);
    }

    public function delete($id)
    {
        return $this->service->delete($id);
    }
}
