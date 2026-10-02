<?php

namespace Modules\Popup\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Popup\Entities\Popup;

class PopupDatabaseSeeder extends Seeder
{
    /**
     * Promotional popups shown on the home screen.
     */
    public function run(): void
    {
        $popups = [
            [
                'image' => 'https://images.unsplash.com/photo-1607083206968-13611e3d76db?auto=format&fit=crop&w=1000&q=80',
                'video' => null,
                'show_on_home_page' => true,
            ],
            [
                'image' => 'https://images.unsplash.com/photo-1483985988355-763728e1935b?auto=format&fit=crop&w=1000&q=80',
                'video' => null,
                'show_on_home_page' => false,
            ],
        ];

        foreach ($popups as $popup) {
            Popup::updateOrCreate(
                ['image' => $popup['image']],
                ['video' => $popup['video'], 'show_on_home_page' => $popup['show_on_home_page']]
            );
        }
    }
}
