<?php

namespace Modules\Notification\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Notification\Http\Requests\NotificationAddRequest;
use Modules\Notification\Services\SendNotificationService;
use Nwidart\Modules\Facades\Module;

class SendNotificationController extends Controller
{
    private SendNotificationService $service;

    public function __construct(SendNotificationService $service)
    {
        $this->middleware('permission:send notification')->only('sendNotification');

        $this->service = $service;
    }

    public function sendNotification(NotificationAddRequest $request)
    {
        return $this->service->sendNotification($request,dryRun: false);
    }

}
