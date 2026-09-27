<?php

namespace Modules\Size\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Size\Http\Entities\Size;

class SizeDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Size::factory()->count(6)->create();
    }
}
