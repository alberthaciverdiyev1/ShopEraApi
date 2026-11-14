<?php

namespace Modules\Product\Services;

use Modules\Product\Entities\Product;
use Modules\Product\Entities\ProductVariant;

class ProductVariantService
{
    /**
     * Replace the product's colour × size matrix.
     *
     * The seller form posts three parallel maps keyed `[colorId][sizeId]`
     * (`variant_prices`, `variant_discounts`, `variant_stocks`); the admin form
     * posts one nested map (`variants[colorId][sizeId][price|discount|stock]`).
     * Both are normalised into rows here.
     */
    public function syncFromMatrix(Product $product, array $prices, array $discounts = [], array $stocks = []): void
    {
        $rows = [];

        // The three maps are parallel but need not all be present: with the
        // standard price on only the stock map is posted, so iterate over the
        // union of their colour keys.
        $colorIds = array_unique(array_merge(
            array_keys($prices),
            array_keys($discounts),
            array_keys($stocks),
        ));

        foreach ($colorIds as $colorId) {
            $sizeIds = array_unique(array_merge(
                array_keys((array) ($prices[$colorId] ?? [])),
                array_keys((array) ($discounts[$colorId] ?? [])),
                array_keys((array) ($stocks[$colorId] ?? [])),
            ));

            foreach ($sizeIds as $sizeId) {
                $rows[] = [
                    'color_id' => $colorId,
                    'size_id' => $sizeId,
                    'price' => $prices[$colorId][$sizeId] ?? null,
                    'discount' => $discounts[$colorId][$sizeId] ?? null,
                    'stock' => $stocks[$colorId][$sizeId] ?? null,
                ];
            }
        }

        $this->replace($product, $rows);
    }

    /** The admin form's nested `variants[color][size][field]` payload. */
    public function syncFromRows(Product $product, array $rows): void
    {
        $normalised = [];

        foreach ($rows as $colorId => $sizes) {
            foreach ((array) $sizes as $sizeId => $cell) {
                if (! is_array($cell)) {
                    continue;
                }

                $normalised[] = [
                    'color_id' => $colorId,
                    'size_id' => $sizeId,
                    'price' => $cell['price'] ?? null,
                    'discount' => $cell['discount'] ?? null,
                    'wholesale_price' => $cell['wholesale_price'] ?? null,
                    'stock' => $cell['stock'] ?? null,
                ];
            }
        }

        $this->replace($product, $normalised);
    }

    /**
     * The matrix as a flat map the storefront can look up when both a colour
     * and a size are selected: `"colorId:sizeId" => [price, discount, stock]`.
     */
    public function matrix(Product $product): array
    {
        return $product->variants()
            ->get()
            ->mapWithKeys(fn (ProductVariant $variant) => [
                ((int) $variant->color_id).':'.((int) $variant->size_id) => [
                    'price' => $variant->price,
                    'discount' => $variant->discount,
                    'stock' => $variant->stock,
                ],
            ])
            ->all();
    }

    /** @param  array<int, array<string, mixed>>  $rows */
    private function replace(Product $product, array $rows): void
    {
        $colorIds = $product->colors()->pluck('colors.id')->map(fn ($id) => (int) $id)->all();
        $sizeIds = $product->sizes()->pluck('sizes.id')->map(fn ($id) => (int) $id)->all();

        $normalised = [];

        foreach ($rows as $row) {
            $colorId = $this->nullableId($row['color_id'] ?? null);
            $sizeId = $this->nullableId($row['size_id'] ?? null);

            if ($colorId !== null && ! in_array($colorId, $colorIds, true)) {
                continue;
            }

            if ($sizeId !== null && ! in_array($sizeId, $sizeIds, true)) {
                continue;
            }

            if ($colorId === null && $sizeId === null) {
                continue;
            }

            $price = $this->nullableNumber($row['price'] ?? null);
            $discount = $this->nullableNumber($row['discount'] ?? null);
            $wholesale = $this->nullableNumber($row['wholesale_price'] ?? null);
            $stock = $this->nullableNumber($row['stock'] ?? null);

            // An entirely empty cell is "not sold in this combination".
            if ($price === null && $discount === null && $wholesale === null && $stock === null) {
                continue;
            }

            $normalised[$colorId.':'.$sizeId] = [
                'product_id' => $product->id,
                'color_id' => $colorId,
                'size_id' => $sizeId,
                'price' => $price,
                'discount' => $discount,
                'wholesale_price' => $wholesale,
                'stock' => $stock !== null ? max(0, (int) $stock) : null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        $product->variants()->delete();

        if ($normalised !== []) {
            ProductVariant::query()->insert(array_values($normalised));
        }

        $product->unsetRelation('variants');
    }

    private function nullableId(mixed $value): ?int
    {
        if ($value === null || $value === '' || ! is_numeric($value)) {
            return null;
        }

        $id = (int) $value;

        return $id > 0 ? $id : null;
    }

    private function nullableNumber(mixed $value): ?float
    {
        if ($value === null || $value === '' || ! is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }
}
