<?php

namespace Modules\Notification\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\Notification\Http\Requests\NotificationTokenRequest;
use Modules\Notification\Services\NotificationTokenService;

class NotificationTokenController extends Controller
{
    private NotificationTokenService $service;

    public function __construct(NotificationTokenService $service)
    {
        $this->service = $service;
    }

    public function saveToken(NotificationTokenRequest $request)
    {
        $validated = $request->validated();

        return $this->service->updateOrCreate($validated);
    }
}
