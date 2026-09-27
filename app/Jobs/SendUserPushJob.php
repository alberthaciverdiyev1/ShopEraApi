<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Notification\Http\Entities\NotificationToken;
use Modules\Notification\Services\SendNotificationService;

/**
 * Delivers an already-stored notification to one user's devices.
 *
 * NotificationService writes the database row first and queues this after, so a
 * user with no device — or a dead token — never loses the in-app notification.
 */
class SendUserPushJob implements ShouldQueue
{
    use Dispatchable, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 120;

    public function __construct(
        public int $userId,
        public string $title,
        public string $body,
        public array $data = [],
        public ?string $icon = null,
        public ?string $image = null,
    ) {
    }

    public function handle(SendNotificationService $service, NotificationToken $tokens): void
    {
        try {
            $deviceTokens = $tokens->newQuery()
                ->where('user_id', $this->userId)
                ->where('is_active', true)
                ->whereNotNull('token')
                ->pluck('token')
                ->all();

            if (empty($deviceTokens)) {
                return;
            }

            $result = $service->sendToMultiple(
                $deviceTokens,
                $this->title,
                $this->body,
                $this->icon,
                $this->data,
                false,
                $this->image,
            );

            if (($result['failure'] ?? 0) > 0) {
                Log::warning('Some devices did not receive the notification.', [
                    'user_id' => $this->userId,
                    'success' => $result['success'] ?? 0,
                    'failure' => $result['failure'] ?? 0,
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('User push could not be delivered.', [
                'user_id' => $this->userId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
