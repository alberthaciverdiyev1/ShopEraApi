<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Modules\Category\Entities\Category;
use Modules\CjDropShopping\Services\DCategoryService;
use Tests\TestCase;

class DCategorySyncTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('module:migrate', ['module' => 'Category']);

        // Only the key is overridden; base_url must resolve from the module
        // config (regression guard for the wrong config key).
        config(['cjdropshopping.api_key' => 'test-key']);

        $this->assertNotEmpty(config('cjdropshopping.base_url'));
    }

    public function test_it_imports_cj_categories_with_their_hierarchy(): void
    {
        $this->fakeCj();

        $summary = app(DCategoryService::class)->sync(false);

        $this->assertSame(3, $summary['fetched']);
        $this->assertSame(3, $summary['created']);

        $root = Category::where('cj_category_id', '100')->firstOrFail();
        $child = Category::where('cj_category_id', '101')->firstOrFail();

        $this->assertNull($root->parent_id);
        $this->assertSame($root->id, $child->parent_id);
        $this->assertSame('Electronics', $root->getTranslations('name')['en']);
    }

    public function test_sync_is_idempotent(): void
    {
        $this->fakeCj();

        app(DCategoryService::class)->sync(false);
        $summary = app(DCategoryService::class)->sync(false);

        $this->assertSame(0, $summary['created']);
        $this->assertSame(3, $summary['updated']);
        $this->assertSame(3, Category::query()->count());
    }

    private function fakeCj(): void
    {
        Http::fake([
            '*/authentication/getAccessToken' => Http::response([
                'code' => 200,
                'data' => [
                    'accessToken' => 'AT',
                    'accessTokenExpiryDate' => now()->addHour()->toIso8601String(),
                ],
            ]),
            '*/product/getCategory' => Http::response([
                'code' => 200,
                'data' => [
                    ['categoryId' => '100', 'categoryName' => 'Electronics', 'categoryParentId' => '0', 'categoryLevel' => 1],
                    ['categoryId' => '101', 'categoryName' => 'Phones', 'categoryParentId' => '100', 'categoryLevel' => 2],
                    ['categoryId' => '102', 'categoryName' => 'Accessories', 'categoryParentId' => '100', 'categoryLevel' => 2],
                ],
            ]),
        ]);
    }
}
