<?php

namespace Modules\Notification\Services;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Modules\Notification\Entities\NotificationToken;

class NotificationTokenService
{
    private NotificationToken $model;

    private NotificationService $notificationService;

    private SendNotificationService $sendNotificationService;

    public function __construct(
        NotificationToken $model,
        NotificationService $notificationService,
        SendNotificationService $sendNotificationService
    ) {
        $this->model = $model;
        $this->notificationService = $notificationService;
        $this->sendNotificationService = $sendNotificationService;
    }

    public function updateOrCreate($validated, $user = null): JsonResponse
    {
        // A guest has no account yet: the device is written down without one
        // and picks up its owner the moment they sign in.
        $user = $user ?? auth('sanctum')->user();

        return handleTransaction(
            function () use ($validated, $user) {
                $tokenModel = $this->model->updateOrCreate(
                    ['token' => $validated['device_token']],
                    [
                        'user_id' => $user?->id,
                        'device_type' => $validated['device_type'] ?? 'android',
                        'is_active' => true,
                        'last_used_at' => now(),
                    ]
                );

                //                try {
                //                    $deviceToken = $validated['device_token'] ?? null;
                //                    if ($deviceToken) {
                //                        $this->sendNotificationService->subscribeToTopic('all_users', [$deviceToken]);
                //                    }
                //                } catch (\Throwable $e) {
                //                    Log::error('Failed to subscribe user to FCM topic: ' . $e->getMessage());
                //                }

                return $tokenModel;
            },
            'Notification token successfully added.',
            null,
            200
        );
    }

    public function deleteToken($request): void
    {
        $this->model->where('token', $request->device_token)
            ->update(['is_active' => false]);
    }
}
