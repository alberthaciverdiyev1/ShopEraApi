<?php

namespace Modules\Banner\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Banner\Http\Entities\Banner;

class BannerDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Banner::factory()->count(4)->create();
    }
}
