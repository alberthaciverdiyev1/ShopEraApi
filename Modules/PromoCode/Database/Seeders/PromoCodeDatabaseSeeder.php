<?php

namespace Modules\PromoCode\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\PromoCode\Http\Entities\PromoCode;

class PromoCodeDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        PromoCode::factory()->count(50)->create();
    }
}
