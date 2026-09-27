<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


Schedule::command('db:backup')->dailyAt('03:00');

Artisan::command('starex:sync-pickup-points {--create-missing}', function () {
    $summary = app(\App\Services\Starex\StarexShipmentService::class)
        ->syncPickupPoints((bool) $this->option('create-missing'));

    $this->line(json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
})->purpose('Synchronize mapped Starex PUDO points and report unmatched points');

Artisan::command('starex:reconcile-shipments', function () {
    $count = app(\App\Services\Starex\StarexShipmentService::class)->reconcileActiveShipments();
    $this->info("{$count} Starex shipment(s) reconciled.");
})->purpose('Reconcile active Starex shipment statuses');

Artisan::command('starex:list-cities', function () {
    $this->line(json_encode(
        app(\App\Services\Starex\StarexClient::class)->cities(),
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
    ));
})->purpose('List Starex cities and IDs');

Artisan::command('starex:list-districts {cityId}', function (int $cityId) {
    $this->line(json_encode(
        app(\App\Services\Starex\StarexClient::class)->districts($cityId),
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
    ));
})->purpose('List Starex districts for a city');

Artisan::command('starex:list-towns {districtId}', function (int $districtId) {
    $this->line(json_encode(
        app(\App\Services\Starex\StarexClient::class)->towns($districtId),
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
    ));
})->purpose('List Starex towns for a district');

Artisan::command('orders:expire-pending-payments {--hours=3} {--limit=200}', function () {
    $summary = app(\App\Services\Order\PendingPaymentExpiryService::class)->expire(
        (int) $this->option('hours'),
        (int) $this->option('limit')
    );

    $this->line(json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
})->purpose('Expire old unpaid card orders and return reserved stock');

Schedule::command('starex:reconcile-shipments')->everyFifteenMinutes()->withoutOverlapping();
Schedule::command('orders:expire-pending-payments --hours=3 --limit=200')->everyFifteenMinutes()->withoutOverlapping();

Artisan::command('stores:process-overdue-handovers', function () {
    $count = app(\Modules\Store\Services\MerchantOrderService::class)->processOverdueFulfillments();
    $this->info("{$count} overdue store handover(s) processed.");
})->purpose('Mark overdue merchant handovers and apply the configured penalty once');

Schedule::command('stores:process-overdue-handovers')->everyFifteenMinutes()->withoutOverlapping();

Artisan::command('stores:send-handover-reminders {--hours=4}', function () {
    $sent = app(\Modules\Store\Services\MerchantOrderService::class)
        ->sendHandoverReminders((int) $this->option('hours'));
    $this->info("{$sent} handover reminder(s) sent.");
})->purpose('Warn merchants before their hand-over deadline expires');

Schedule::command('stores:send-handover-reminders')->hourly()->withoutOverlapping();

Artisan::command('stores:release-settlements {--limit=200}', function () {
    $released = app(\Modules\Store\Services\MerchantOrderService::class)
        ->releaseEligibleSettlements((int) $this->option('limit'));
    $this->info("{$released} settlement(s) released to withdrawable balance.");
})->purpose('Move delivered-order earnings into the withdrawable balance');

Schedule::command('stores:release-settlements')->everyFifteenMinutes()->withoutOverlapping();

/*
 * Live shopping. Going on air is a decision, not something detected: the admin
 * pastes the broadcast link and presses the button. There used to be a
 * `live:sync` here that polled the YouTube Data API for the same thing, but it
 * bought only the polling — at the price of an API key, a channel id, and a
 * quota to watch — so it was dropped.
 */
Schedule::command('live:refresh-viewers')->everyMinute()->withoutOverlapping();

/*
 * Classifieds. An ad past its date already drops out of every list, because
 * the query checks the date; this only writes the status so the owner's
 * cabinet can offer the renew button.
 */
Schedule::command('listings:expire')->hourly()->withoutOverlapping();
