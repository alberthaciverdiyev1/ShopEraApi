<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\Marketplace\Entities\Vendor;
use Modules\Marketplace\Services\VendorService;

class RecalcVendorRatings extends Command
{
    protected $signature = 'vendors:recalc-ratings';

    protected $description = 'Recompute every store rating from its product reviews';

    public function handle(VendorService $service): int
    {
        $count = 0;
        Vendor::query()->chunkById(100, function ($vendors) use ($service, &$count) {
            foreach ($vendors as $vendor) {
                $service->recalcRating($vendor);
                $count++;
            }
        });
        $this->info("Stores recalculated: {$count}");

        return self::SUCCESS;
    }
}
