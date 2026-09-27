<?php

namespace App\Jobs;

use App\Services\Starex\StarexShipmentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CancelStarexShipmentJob implements ShouldQueue
{
    use Dispatchable, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    public function backoff(): array
    {
        return [30, 120, 600];
    }

    public function __construct(public int $orderId)
    {
    }

    public function handle(StarexShipmentService $service): void
    {
        $service->cancelForOrder($this->orderId);
    }
}
