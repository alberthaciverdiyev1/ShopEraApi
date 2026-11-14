<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Color\Entities\Color;
use Modules\Product\Entities\Product;
use Modules\Product\Entities\ProductVariant;
use Modules\Product\Services\ProductVariantService;
use Modules\Size\Entities\Size;
use Tests\TestCase;

class ProductVariantMatrixTest extends TestCase
{
    use RefreshDatabase;

    private function color(string $name): Color
    {
        return Color::query()->create([
            'name' => ['az' => $name, 'en' => $name, 'ru' => $name, 'tr' => $name],
            'hex' => '#112233',
        ]);
    }

    private function makeSize(string $name): Size
    {
        return Size::query()->create([
            'name' => ['az' => $name, 'en' => $name, 'ru' => $name, 'tr' => $name],
        ]);
    }

    /** @param array<int, Color> $colors  @param array<int, Size> $sizes */
    private function product(array $colors = [], array $sizes = []): Product
    {
        $product = Product::query()->create([
            'title' => ['az' => 'Matris', 'en' => 'Matrix', 'ru' => 'Матрица', 'tr' => 'Matris'],
            'price' => 10,
            'stock_count' => 5,
            'is_active' => true,
            'approval_status' => 'approved',
        ]);

        $product->colors()->sync(array_map(fn (Color $color) => $color->id, $colors));
        $product->sizes()->sync(array_map(fn (Size $size) => $size->id, $sizes));

        return $product;
    }

    public function test_the_seller_matrix_persists_price_discount_and_stock_per_combination(): void
    {
        $red = $this->color('Qırmızı');
        $blue = $this->color('Mavi');
        $small = $this->makeSize('S');
        $large = $this->makeSize('L');

        $product = $this->product([$red, $blue], [$small, $large]);

        app(ProductVariantService::class)->syncFromMatrix(
            $product,
            [$red->id => [$small->id => 20, $large->id => 25]],
            [$red->id => [$small->id => 18]],
            [$red->id => [$small->id => 3], $large->id => [$large->id => 7]],
        );

        $this->assertSame(3, ProductVariant::query()->where('product_id', $product->id)->count());

        $redSmall = ProductVariant::query()
            ->where(['product_id' => $product->id, 'color_id' => $red->id, 'size_id' => $small->id])
            ->firstOrFail();

        $this->assertSame(20.0, $redSmall->price);
        $this->assertSame(18.0, $redSmall->discount);
        $this->assertSame(3, $redSmall->stock);

        $blueLarge = ProductVariant::query()
            ->where(['product_id' => $product->id, 'color_id' => $blue->id, 'size_id' => $large->id])
            ->firstOrFail();

        $this->assertNull($blueLarge->price);
        $this->assertSame(7, $blueLarge->stock);
    }

    public function test_combinations_of_colours_or_sizes_not_on_the_product_are_ignored(): void
    {
        $red = $this->color('Qırmızı');
        $green = $this->color('Yaşıl');
        $small = $this->makeSize('S');

        $product = $this->product([$red], [$small]);

        app(ProductVariantService::class)->syncFromMatrix(
            $product,
            [$green->id => [$small->id => 99], $red->id => [$small->id => 11]],
        );

        $this->assertSame(1, ProductVariant::query()->where('product_id', $product->id)->count());
        $this->assertSame(11.0, ProductVariant::query()->firstOrFail()->price);
    }

    public function test_an_empty_cell_is_not_saved_and_a_resync_replaces_old_rows(): void
    {
        $red = $this->color('Qırmızı');
        $small = $this->makeSize('S');
        $large = $this->makeSize('L');

        $product = $this->product([$red], [$small, $large]);
        $service = app(ProductVariantService::class);

        $service->syncFromMatrix($product, [$red->id => [$small->id => 20, $large->id => 25]]);
        $this->assertSame(2, ProductVariant::query()->count());

        // The large size is cleared and the small one re-priced.
        $service->syncFromMatrix($product, [$red->id => [$small->id => 30]]);

        $this->assertSame(1, ProductVariant::query()->count());
        $this->assertSame(30.0, ProductVariant::query()->firstOrFail()->price);
    }

    public function test_the_admin_nested_payload_is_normalised(): void
    {
        $red = $this->color('Qırmızı');
        $small = $this->makeSize('S');

        $product = $this->product([$red], [$small]);

        app(ProductVariantService::class)->syncFromRows($product, [
            $red->id => [
                $small->id => ['price' => '12.5', 'discount' => '', 'wholesale_price' => 9, 'stock' => '4'],
            ],
        ]);

        $variant = ProductVariant::query()->firstOrFail();

        $this->assertSame(12.5, $variant->price);
        $this->assertNull($variant->discount);
        $this->assertSame(9.0, $variant->wholesale_price);
        $this->assertSame(4, $variant->stock);
    }

    public function test_the_matrix_lookup_is_keyed_by_colour_and_size(): void
    {
        $red = $this->color('Qırmızı');
        $small = $this->makeSize('S');

        $product = $this->product([$red], [$small]);

        app(ProductVariantService::class)->syncFromMatrix(
            $product,
            [$red->id => [$small->id => 15]],
            [],
            [$red->id => [$small->id => 2]],
        );

        $matrix = app(ProductVariantService::class)->matrix($product);

        $this->assertSame([
            $red->id.':'.$small->id => ['price' => 15.0, 'discount' => null, 'stock' => 2],
        ], $matrix);
    }
}
