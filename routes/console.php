<?php

use App\Services\Order\PendingPaymentExpiryService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('db:backup')->dailyAt('03:00');

Artisan::command('orders:expire-pending-payments {--hours=3} {--limit=200}', function () {
    $hours = (int) $this->option('hours');
    $limit = (int) $this->option('limit');

    $result = app(PendingPaymentExpiryService::class)->expire($hours, $limit);

    $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
})->purpose('Expire old unpaid card orders and return reserved stock');

Schedule::command('orders:expire-pending-payments --hours=3 --limit=200')->everyFifteenMinutes()->withoutOverlapping();
