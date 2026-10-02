<?php

namespace Modules\Size\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Size\Entities\Size;

class SizeDatabaseSeeder extends Seeder
{
    /**
     * Clothing letters followed by EU shoe numbers — the sizes the catalogue
     * actually sells in. Matching is done in PHP because `name` is a plain
     * string column holding JSON (not jsonb).
     */
    public function run(): void
    {
        $sizes = ['XS', 'S', 'M', 'L', 'XL', 'XXL'];

        foreach (range(36, 45) as $shoe) {
            $sizes[] = (string) $shoe;
        }

        $existing = Size::all()->keyBy(fn (Size $s) => $s->getTranslation('name', 'en'));

        foreach ($sizes as $index => $size) {
            $payload = [
                'name' => ['az' => $size, 'en' => $size, 'ru' => $size, 'tr' => $size],
                'icon' => null,
                'sort_order' => $index,
                'is_active' => true,
            ];

            if ($existingSize = $existing->get($size)) {
                $existingSize->update($payload);
            } else {
                Size::create($payload);
            }
        }
    }
}
