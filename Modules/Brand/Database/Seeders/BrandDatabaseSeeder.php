<?php

namespace Modules\Brand\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Brand\Http\Entities\Brand;

class BrandDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Brand::factory()->count(200)->create();
    }
}
