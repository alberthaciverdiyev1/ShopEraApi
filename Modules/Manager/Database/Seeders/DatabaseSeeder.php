<?php

namespace Modules\Manager\Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            FeatureSeeder::class,
            PlanSeeder::class,
            ThemeSeeder::class,
            PromoBlockSeeder::class,
            OwnerSeeder::class,
        ]);
    }
}
