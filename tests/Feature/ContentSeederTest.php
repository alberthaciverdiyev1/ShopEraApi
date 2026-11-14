<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\HelpAndPolicy\Database\Seeders\ContentDatabaseSeeder;
use Modules\HelpAndPolicy\Entities\Faq;
use Modules\HelpAndPolicy\Entities\LegalTerm;
use Tests\TestCase;

class ContentSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ContentDatabaseSeeder::class);
    }

    public function test_it_seeds_the_legal_pages_in_every_locale(): void
    {
        $this->assertSame(3, LegalTerm::count());

        foreach (['about', 'main_page', 'register_page'] as $type) {
            $term = LegalTerm::where('type', $type)->firstOrFail();
            $html = $term->getTranslations('html');

            foreach (['az', 'en', 'ru', 'tr'] as $locale) {
                $this->assertNotEmpty($html[$locale] ?? null, "{$type} has no {$locale} content");
            }

            $this->assertStringContainsString('ShopEra', $html['az']);
            $this->assertStringNotContainsString('Snaker', $html['az']);
        }
    }

    public function test_it_seeds_the_help_questions_in_every_locale(): void
    {
        $this->assertSame(13, Faq::count());

        $reset = Faq::query()->where('title->az', 'Şifrəmi unutmuşam, nə etməliyəm?')->firstOrFail();
        $description = $reset->getTranslations('description');

        $this->assertStringContainsString('WhatsApp', $description['az']);
        $this->assertStringContainsString('WhatsApp', $description['en']);

        foreach (Faq::all() as $faq) {
            $title = $faq->getTranslations('title');

            foreach (['az', 'en', 'ru', 'tr'] as $locale) {
                $this->assertNotEmpty($title[$locale] ?? null, "FAQ #{$faq->id} has no {$locale} title");
            }
        }
    }
}
