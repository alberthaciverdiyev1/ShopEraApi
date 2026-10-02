<?php

namespace Modules\HelpAndPolicy\Database\Seeders;

use Illuminate\Database\Seeder;

class HelpAndPolicyDatabaseSeeder extends Seeder
{
    /**
     * Storefront help & policy content (legal pages + FAQ), delegated to the
     * curated content seeder so there is a single source of truth.
     */
    public function run(): void
    {
        $this->call([
            ContentDatabaseSeeder::class,
        ]);
    }
}
