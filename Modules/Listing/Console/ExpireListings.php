<?php

namespace Modules\Listing\Console;

use Illuminate\Console\Command;
use Modules\Listing\Http\Entities\Listing;

/**
 * An ad that has run its time drops out of the lists on its own, because
 * `visible()` checks the date. This only writes that fact into the status, so
 * the owner's cabinet can show it as expired and offer the renew button.
 */
class ExpireListings extends Command
{
    protected $signature = 'listings:expire {--limit=500}';

    protected $description = 'Move listings whose time has run out into the expired state';

    public function handle(): int
    {
        $ids = Listing::query()
            ->where('status', Listing::STATUS_ACTIVE)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->limit(max((int) $this->option('limit'), 1))
            ->pluck('id');

        if ($ids->isEmpty()) {
            $this->info('0 listing(s) expired.');

            return self::SUCCESS;
        }

        // A plain update: no model events, and updated_at stays as the seller
        // left it, so "last edited" keeps meaning what it says.
        Listing::whereIn('id', $ids)->update(['status' => Listing::STATUS_EXPIRED]);

        $this->info($ids->count() . ' listing(s) expired.');

        return self::SUCCESS;
    }
}
