<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\Marketplace\Services\PromotionService;

class ExpirePromotions extends Command
{
    protected $signature = 'listings:expire-promotions';

    protected $description = 'Clear expired listing promotions (promoted/premium flags)';

    public function handle(PromotionService $service): int
    {
        $count = $service->expire();
        $this->info("Expired promotions cleared: {$count}");

        return self::SUCCESS;
    }
}
