<?php

namespace Modules\Color\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Color\Http\Entities\Color;

class ColorDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
       Color::factory()->count(20)->create();
    }
}
