<?php

namespace Modules\Product\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Product\Http\Entities\Product;
use Modules\Product\Http\Entities\Review;

class ProductDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Product::factory()->count(200)->create();
        Review::factory()->count(200)->create();
    }
}
