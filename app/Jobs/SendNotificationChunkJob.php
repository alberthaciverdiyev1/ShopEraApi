<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Notification\Services\SendNotificationService;

class SendNotificationChunkJob implements ShouldQueue
{
    use Dispatchable, Queueable, SerializesModels;

    public int $tries = 1;

    /**
     * A chunk is SendNotificationService::BROADCAST_CHUNK devices sent 100 at
     * a time with a 15 s per-request ceiling, so even a stalled FCM finishes
     * inside this.
     */
    public int $timeout = 120;

    public function __construct(
        public array $tokens,
        public string $title,
        public string $body,
        public ?string $icon = null,
        public array $data = [],
        public bool $dryRun = false,
        public ?string $imageUrl = null,
        public ?string $url = null
    ) {
    }

    public function handle(SendNotificationService $service): void
    {
        $result = $service->sendToMultiple(
            $this->tokens,
            $this->title,
            $this->body,
            $this->icon,
            $this->data,
            $this->dryRun,
            $this->imageUrl,
            $this->url
        );

        if (isset($result['error']) || ($result['failure'] ?? 0) > 0) {
            Log::warning('Bulk notification chunk was not fully delivered.', [
                'success' => $result['success'] ?? 0,
                'failure' => $result['failure'] ?? 0,
            ]);
        }
    }
}
