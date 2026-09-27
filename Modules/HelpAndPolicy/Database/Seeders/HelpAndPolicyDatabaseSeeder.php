<?php

namespace Modules\HelpAndPolicy\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\HelpAndPolicy\Http\Entities\Faq;
use Modules\HelpAndPolicy\Http\Entities\LegalTerm;

class HelpAndPolicyDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
       // Faq::factory()->count(50)->create();

        LegalTerm::create([
            'type' => 'register_page',
            'html' => [
                'az' => '<h1>Xidmət Şərtləri</h1><p>Bunlar xidmət şərtləridir...</p>',
                'en' => '<h1>Terms of Service</h1><p>These are the terms of service...</p>',
                'ru' => '<h1>Условия обслуживания</h1><p>Это условия обслуживания...</p>',
                'tr' => '<h1>Hizmet Şartları</h1><p>Bunlar hizmet şartlarıdır...</p>',
            ],
        ]);

        LegalTerm::create([
            'type' => 'main_page',
            'html' => [
                'az' => '<h1>Məxfilik Siyasəti</h1><p>Bu məxfilik siyasətidir...</p>',
                'en' => '<h1>Privacy Policy</h1><p>This is the privacy policy...</p>',
                'ru' => '<h1>Политика конфиденциальности</h1><p>Это политика конфиденциальности...</p>',
                'tr' => '<h1>Gizlilik Politikası</h1><p>Bu gizlilik politikasıdır...</p>',
            ],
        ]);

    }
}
