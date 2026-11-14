<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Setting\Database\Seeders\FooterLinkDatabaseSeeder;
use Modules\Setting\Entities\FooterLink;
use Tests\TestCase;

class FooterLinksTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_footer_renders_only_active_links_from_the_admin(): void
    {
        FooterLink::query()->create([
            'section' => 'catalog',
            'label' => ['az' => 'Kampaniyalar', 'en' => 'Campaigns'],
            'url' => '/shop?campaign=1',
            'sort_order' => 10,
            'is_active' => true,
        ]);

        FooterLink::query()->create([
            'section' => 'information',
            'label' => ['az' => 'Gizli link', 'en' => 'Hidden link'],
            'url' => '/hidden',
            'sort_order' => 10,
            'is_active' => false,
        ]);

        $html = $this->get('/');

        $html->assertSee('Kataloq');
        $html->assertSee('Kampaniyalar');
        $html->assertSee('/shop?campaign=1');
        $html->assertDontSee('Gizli link');
        $html->assertDontSee('Məxfilik siyasəti');
    }

    public function test_the_seeder_installs_the_familiar_footer_columns(): void
    {
        $this->seed(FooterLinkDatabaseSeeder::class);

        $this->assertSame(10, FooterLink::count());
        $this->assertSame(3, FooterLink::where('section', 'catalog')->count());
        $this->assertSame(4, FooterLink::where('section', 'information')->count());
        $this->assertSame(3, FooterLink::where('section', 'contact')->count());

        $about = FooterLink::query()->where('url', '/about')->firstOrFail();
        $labels = $about->getTranslations('label');

        foreach (['az', 'en', 'ru', 'tr'] as $locale) {
            $this->assertNotEmpty($labels[$locale] ?? null, "About link has no {$locale} label");
        }
    }
}
