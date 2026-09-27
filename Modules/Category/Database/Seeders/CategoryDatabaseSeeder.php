<?php

namespace Modules\Category\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Category\Http\Entities\Category;

class CategoryDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
//        $parentCategories = Category::factory()->count(50)->create([
//            'parent_id' => null
//        ]);
//
//        foreach ($parentCategories as $parent) {
//            $numChildren = rand(1, 5);
//            Category::factory()->count($numChildren)->create([
//                'parent_id' => $parent->id
//            ]);
//        }
    }
}
