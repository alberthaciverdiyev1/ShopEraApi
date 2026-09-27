<?php

namespace App\Jobs;

use App\Services\Starex\StarexShipmentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CreateStarexShipmentJob implements ShouldQueue
{
    use Dispatchable, Queueable, SerializesModels;

    // A timed-out create response is ambiguous. Manual retry avoids duplicate packages.
    public int $tries = 1;

    public int $timeout = 60;

    public function __construct(public int $orderId)
    {
    }

    public function handle(StarexShipmentService $service): void
    {
        $service->createForOrder($this->orderId);
    }
}
