<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Setting\Database\Seeders\SocialLinkDatabaseSeeder;
use Modules\Setting\Entities\SocialLink;
use Tests\TestCase;

class SocialLinksTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_footer_renders_only_active_social_links_from_the_admin(): void
    {
        SocialLink::query()->create([
            'platform' => 'facebook',
            'url' => 'https://facebook.com/shopera',
            'sort_order' => 10,
            'is_active' => true,
        ]);

        SocialLink::query()->create([
            'platform' => 'whatsapp',
            'url' => 'https://wa.me/994500000000',
            'sort_order' => 20,
            'is_active' => true,
        ]);

        SocialLink::query()->create([
            'platform' => 'tiktok',
            'url' => 'https://tiktok.com/@gizli',
            'sort_order' => 30,
            'is_active' => false,
        ]);

        $html = $this->get('/');

        $html->assertSee('https://facebook.com/shopera');
        $html->assertSee('fa-facebook-f');
        $html->assertSee('https://wa.me/994500000000');
        $html->assertSee('fa-whatsapp');
        $html->assertSee('aria-label="WhatsApp"', false);
        $html->assertDontSee('https://tiktok.com/@gizli');
    }

    public function test_the_seeder_installs_the_default_networks(): void
    {
        $this->seed(SocialLinkDatabaseSeeder::class);

        $this->assertSame(3, SocialLink::count());
        $this->assertSame(
            ['facebook', 'instagram', 'telegram'],
            SocialLink::query()->orderBy('sort_order')->pluck('platform')->all(),
        );
        $this->assertTrue(SocialLink::query()->where('is_active', true)->count() === 3);
    }
}
