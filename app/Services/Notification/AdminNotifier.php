<?php

namespace App\Services\Notification;

use App\Jobs\SendUserPushJob;
use Illuminate\Support\Facades\Log;
use Modules\Notification\Services\NotificationService;
use Modules\User\Entities\User;

/**
 * Notifies the staff who work the marketplace approval queues.
 *
 * The admin panel already shows the queues, but nothing told anyone a new item
 * had arrived — a store could sit unapproved for a day because no one looked.
 */
class AdminNotifier
{
    private const ROLES = ['admin', 'manager', 'developer'];

    public function __construct(private NotificationService $notifications) {}

    public function notify(string $title, string $body, array $data = []): void
    {
        try {
            $userIds = User::whereHas('roles', fn ($query) => $query->whereIn('name', self::ROLES))
                ->pluck('id')
                ->all();

            if (empty($userIds)) {
                return;
            }

            // One notification row shared by every recipient, as the bulk
            // sender already does.
            $this->notifications->addMultiple([
                'title' => $title,
                'body' => $body,
                'data' => $data,
            ], $userIds);

            foreach ($userIds as $userId) {
                SendUserPushJob::dispatch((int) $userId, $title, $body, $data)->afterCommit();
            }
        } catch (\Throwable $e) {
            // Staff alerts are never worth failing a merchant's request over.
            Log::error('Admin notification failed.', [
                'title' => $title,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
