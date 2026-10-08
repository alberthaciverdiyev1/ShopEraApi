<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\Product\Entities\Product;

class ExpireListings extends Command
{
    protected $signature = 'listings:expire';

    protected $description = 'Deactivate listings whose lifetime has ended';

    public function handle(): int
    {
        $count = Product::query()
            ->where('is_active', true)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->update(['is_active' => false]);

        $this->info("Listings archived: {$count}");

        return self::SUCCESS;
    }
}
