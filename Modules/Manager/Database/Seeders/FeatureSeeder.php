<?php

namespace Modules\Manager\Database\Seeders;

use Modules\Manager\Entities\Feature;
use Illuminate\Database\Seeder;

/**
 * Feature catalogue derived from what the ShopEra API + Svelte storefront
 * actually expose. Nothing speculative is listed here.
 */
class FeatureSeeder extends Seeder
{
    public function run(): void
    {
        // key, name, type(bool|limit), default, group
        $features = [
            // Feature kataloqu boşdur — feature'lar Manager panelindən əl ilə əlavə olunur.
        ];

        $keys = array_column($features, 0);

        // Remove anything that is not part of the real capability set.
        Feature::query()->whereNotIn('key', $keys)->delete();

        foreach ($features as $i => [$key, $name, $type, $default, $group]) {
            Feature::query()->updateOrCreate(
                ['key' => $key],
                ['name' => $name, 'type' => $type, 'default_value' => $default, 'group' => $group, 'sort_order' => $i]
            );
        }
    }
}
