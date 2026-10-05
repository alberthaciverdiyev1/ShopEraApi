<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Modules\Category\Entities\Category;
use Modules\CjDropShopping\Entities\DropshippingProductDetail;
use Modules\CjDropShopping\Services\DProductService;
use Modules\Product\Entities\Product;
use Modules\User\Entities\User;
use Tests\TestCase;

class DProductSyncTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach (['Brand', 'Category', 'Color', 'Size', 'User', 'Product', 'CjDropShopping'] as $module) {
            $this->artisan('module:migrate', ['module' => $module]);
        }

        config(['cjdropshopping.api_key' => 'test-key']);
        Storage::fake('public');

        User::query()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'phone' => '0500000000',
            'password' => Hash::make('secret'),
        ]);

        Category::query()->create([
            'cj_category_id' => 'CJ-CAT-1',
            'name' => ['en' => 'Hair Tools'],
        ]);
    }

    public function test_it_imports_the_merchants_own_cj_products(): void
    {
        $this->fakeCj();

        $summary = app(DProductService::class)->sync(false);

        $this->assertSame(1, $summary['created']);
        $this->assertSame(2, $summary['images']);

        $product = Product::query()->firstOrFail();
        $this->assertSame('CJT-DEMO', $product->sku);
        $this->assertSame(9.0, (float) $product->price);
        $this->assertSame(Category::query()->value('id'), $product->category_id);
        $this->assertSame('Demo Hair Tool', $product->getTranslations('title')['en']);
        $this->assertSame(2, $product->images()->count());
        $this->assertNotNull($product->user_id);

        $detail = DropshippingProductDetail::query()->firstOrFail();
        $this->assertSame('P1', $detail->cj_product_id);
        $this->assertSame('US Warehouse', $detail->warehouse);
        $this->assertSame(213.0, $detail->weight_grams);
        $this->assertCount(1, $detail->variants);
    }

    public function test_sync_is_idempotent(): void
    {
        $this->fakeCj();

        app(DProductService::class)->sync(false);
        $summary = app(DProductService::class)->sync(false);

        $this->assertSame(0, $summary['created']);
        $this->assertSame(1, $summary['updated']);
        $this->assertSame(1, Product::query()->count());
        $this->assertSame(2, Product::query()->first()->images()->count());
    }

    private function fakeCj(): void
    {
        Http::fake([
            '*/authentication/getAccessToken' => Http::response([
                'code' => 200,
                'data' => ['accessToken' => 'AT', 'accessTokenExpiryDate' => now()->addHour()->toIso8601String()],
            ]),
            '*/product/myProduct/query*' => Http::response([
                'code' => 200,
                'data' => [
                    'pageSize' => 100,
                    'pageNumber' => 1,
                    'totalRecords' => 1,
                    'totalPages' => 1,
                    'content' => [[
                        'productId' => 'P1',
                        'vid' => 'V1',
                        'nameEn' => 'Demo Hair Tool',
                        'sku' => 'CJT-DEMO',
                        'bigImage' => 'https://cdn.example.com/big.jpg',
                        'sellPrice' => '9.00',
                        'packWeight' => '213',
                        'defaultArea' => 'US Warehouse',
                        'areaCountryCode' => 'US',
                        'shopMethod' => 'USPS',
                        'listedShopNum' => '0',
                        'isFreeShipping' => false,
                    ]],
                ],
            ]),
            '*/product/query*' => Http::response([
                'code' => 200,
                'data' => [
                    'pid' => 'P1',
                    'productNameEn' => 'Demo Hair Tool',
                    'description' => '<p>Demo</p>',
                    'productSku' => 'CJT-DEMO',
                    'productImage' => 'https://cdn.example.com/main.jpg',
                    'productImageSet' => ['https://cdn.example.com/main.jpg'],
                    'productWeight' => '213',
                    'categoryId' => 'CJ-CAT-1',
                    'categoryName' => 'Hair Tools',
                    'sellPrice' => '9.00-12.00',
                    'variants' => [['vid' => 'V1', 'variantSellPrice' => '9.00', 'inventoryNum' => 5]],
                ],
            ]),
            'https://cdn.example.com/*' => Http::response('binary-image', 200),
        ]);
    }
}
