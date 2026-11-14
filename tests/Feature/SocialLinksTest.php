<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
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

    public function test_the_header_strip_only_shows_networks_marked_for_it(): void
    {
        SocialLink::query()->create([
            'platform' => 'facebook',
            'url' => 'https://facebook.com/shopera',
            'sort_order' => 10,
            'is_active' => true,
            'show_in_header' => true,
        ]);

        SocialLink::query()->create([
            'platform' => 'whatsapp',
            'url' => 'https://wa.me/994500000000',
            'sort_order' => 20,
            'is_active' => true,
            'show_in_header' => false,
        ]);

        $html = (string) $this->get('/')->getContent();
        $strip = Str::betweenFirst($html, '<nav class="header-social"', '</nav>');

        $this->assertStringContainsString('fa-facebook-f', $strip);
        $this->assertStringNotContainsString('fa-whatsapp', $strip);

        // The footer still carries every active network.
        $this->assertStringContainsString('https://wa.me/994500000000', $html);
    }

    public function test_the_seeder_installs_the_default_networks(): void
    {
        $this->seed(SocialLinkDatabaseSeeder::class);

        $this->assertSame(8, SocialLink::count());
        $this->assertSame(
            ['facebook', 'instagram', 'telegram', 'whatsapp', 'youtube', 'tiktok', 'x', 'linkedin'],
            SocialLink::query()->orderBy('sort_order')->pluck('platform')->all(),
        );
        $this->assertTrue(SocialLink::query()->where('is_active', true)->count() === 8);
    }

    public function test_reseeding_does_not_overwrite_admin_edited_urls(): void
    {
        SocialLink::query()->create([
            'platform' => 'facebook',
            'url' => 'https://facebook.com/secilmis-hesab',
            'sort_order' => 10,
            'is_active' => true,
        ]);

        $this->seed(SocialLinkDatabaseSeeder::class);

        $this->assertSame(
            'https://facebook.com/secilmis-hesab',
            SocialLink::query()->where('platform', 'facebook')->value('url'),
        );
        $this->assertSame(8, SocialLink::count());
    }
}
