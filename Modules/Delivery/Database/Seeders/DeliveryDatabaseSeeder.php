<?php

namespace Modules\Delivery\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Delivery\Http\Entities\Delivery;

class DeliveryDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Delivery::factory()->count(50)->create();
        // $this->call([]);
    }
}
