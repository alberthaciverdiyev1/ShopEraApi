<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Notification\Services\SendNotificationService;

class SendOrderStatusNotificationJob implements ShouldQueue
{
    use Dispatchable, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 120;

    public function __construct(
        public int $userId,
        public int $orderId,
        public string $status,
        public string $title,
        public string $body,
        public array $extraData = []
    ) {
    }

    public function handle(SendNotificationService $service): void
    {
        try {
            $result = $service->sendOrderStatusNotification(
                $this->userId,
                $this->title,
                $this->body,
                $this->extraData
            );

            if ($result === false || isset($result['error']) || ($result['failure'] ?? 0) > 0) {
                Log::warning('Queued order status notification was not fully delivered.', [
                    'order_id' => $this->orderId,
                    'user_id' => $this->userId,
                    'status' => $this->status,
                    'result' => $result,
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('Queued order status notification failed.', [
                'order_id' => $this->orderId,
                'user_id' => $this->userId,
                'status' => $this->status,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
