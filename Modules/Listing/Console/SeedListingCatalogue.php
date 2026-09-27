<?php

namespace Modules\Listing\Console;

use Illuminate\Console\Command;
use Modules\Listing\Database\Seeders\ListingCatalogueSeeder;
use Modules\Listing\Http\Entities\ListingAttribute;
use Modules\Listing\Http\Entities\ListingAttributeOption;
use Modules\Listing\Http\Entities\ListingSection;

/**
 * Fills in the vehicle and property sections the classifieds start with.
 * Safe to run again: it only adds what is missing, so admin edits stand.
 */
class SeedListingCatalogue extends Command
{
    protected $signature = 'listings:seed-catalogue';

    protected $description = 'Create the vehicle and property sections with their standard fields';

    public function handle(ListingCatalogueSeeder $seeder): int
    {
        $seeder->setCommand($this);
        $seeder->run();

        $this->info(sprintf(
            '%d section(s), %d field(s), %d option(s) in place.',
            ListingSection::count(),
            ListingAttribute::count(),
            ListingAttributeOption::count()
        ));

        return self::SUCCESS;
    }
}
