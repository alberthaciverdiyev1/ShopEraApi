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

    public function test_it_imports_a_flat_cj_category_list(): void
    {
        $this->fakeCj([
            ['categoryId' => '100', 'categoryName' => 'Electronics', 'categoryParentId' => '0'],
            ['categoryId' => '101', 'categoryName' => 'Phones', 'categoryParentId' => '100'],
            ['categoryId' => '102', 'categoryName' => 'Accessories', 'categoryParentId' => '100'],
        ]);

        $summary = app(DCategoryService::class)->sync(false);

        $this->assertSame(3, $summary['fetched']);
        $this->assertSame(3, $summary['created']);

        $root = Category::where('cj_category_id', '100')->firstOrFail();
        $child = Category::where('cj_category_id', '101')->firstOrFail();

        $this->assertNull($root->parent_id);
        $this->assertSame($root->id, $child->parent_id);
        $this->assertSame('Electronics', $root->getTranslations('name')['en']);
    }

    public function test_it_imports_the_real_nested_cj_shape(): void
    {
        // CJ nests three differently-keyed levels.
        $this->fakeCj([
            [
                'categoryFirstId' => 'F1',
                'categoryFirstName' => 'Women',
                'categoryFirstList' => [
                    [
                        'categorySecondId' => 'S1',
                        'categorySecondName' => 'Tops',
                        'categorySecondList' => [
                            ['categoryId' => 'C1', 'categoryName' => 'Blouses'],
                            ['categoryId' => 'C2', 'categoryName' => 'Shirts'],
                        ],
                    ],
                ],
            ],
        ]);

        $summary = app(DCategoryService::class)->sync(false);

        $this->assertSame(4, $summary['fetched']);

        $first = Category::where('cj_category_id', 'F1')->firstOrFail();
        $second = Category::where('cj_category_id', 'S1')->firstOrFail();
        $third = Category::where('cj_category_id', 'C1')->firstOrFail();

        $this->assertNull($first->parent_id);
        $this->assertSame($first->id, $second->parent_id);
        $this->assertSame($second->id, $third->parent_id);
    }

    public function test_sync_is_idempotent(): void
    {
        $this->fakeCj([
            ['categoryId' => '100', 'categoryName' => 'Electronics', 'categoryParentId' => '0'],
            ['categoryId' => '101', 'categoryName' => 'Phones', 'categoryParentId' => '100'],
            ['categoryId' => '102', 'categoryName' => 'Accessories', 'categoryParentId' => '100'],
        ]);

        app(DCategoryService::class)->sync(false);
        $summary = app(DCategoryService::class)->sync(false);

        $this->assertSame(0, $summary['created']);
        $this->assertSame(3, $summary['updated']);
        $this->assertSame(3, Category::query()->count());
    }

    private function fakeCj(array $categories): void
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
                'data' => $categories,
            ]),
        ]);
    }
}
