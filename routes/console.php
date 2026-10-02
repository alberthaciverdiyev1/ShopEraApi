<?php

use App\Services\Order\PendingPaymentExpiryService;
use App\Support\TenantDatabase;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('db:backup')->dailyAt('03:00');

Artisan::command('orders:expire-pending-payments {--hours=3} {--limit=200}', function () {
    $hours = (int) $this->option('hours');
    $limit = (int) $this->option('limit');
    $results = [];

    // Every tenant has its own database; run the expiry once per database.
    TenantDatabase::each(function (?string $database) use ($hours, $limit, &$results) {
        try {
            $results[$database ?? 'central'] = app(PendingPaymentExpiryService::class)->expire($hours, $limit);
        } catch (Throwable $e) {
            $results[$database ?? 'central'] = ['error' => $e->getMessage()];
            Log::error('Pending payment expiry failed.', [
                'database' => $database,
                'error' => $e->getMessage(),
            ]);
        }
    });

    $this->line(json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
})->purpose('Expire old unpaid card orders and return reserved stock');

Schedule::command('orders:expire-pending-payments --hours=3 --limit=200')->everyFifteenMinutes()->withoutOverlapping();

Schedule::command('manager:map')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('manager:report-usage')->hourly();
