<?php

namespace Modules\CjDropShopping\Services;

use App\Helpers\TranslateHelper as Translate;
use App\Support\PlanLimits;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Category\Entities\Category;
use Modules\CjDropShopping\Entities\DropshippingProductDetail;
use Modules\Product\Entities\Product;
use Modules\Product\Services\ProductService;
use Modules\User\Entities\User;
use RuntimeException;
use Throwable;

/**
 * CJ Dropshipping product endpoints and import.
 *
 * `products()`/`product()` read CJ's product library; `myProducts()` lists only
 * the products the merchant added in the CJ panel. `sync()` imports those into
 * the local catalogue, storing CJ-only fields in dropshipping_product_details.
 */
class DProductService extends DBaseService
{
    private const PER_PAGE = 100;

    private const MAX_PAGES = 200;

    private const MAX_IMAGES = 20;

    /** @var array<string,string> per-run translation memo, keyed by "lang|text" */
    private array $translations = [];

    public function __construct(
        DAuthService $auth,
        private readonly ProductService $productService,
    ) {
        parent::__construct($auth);
    }

    /**
     * Search/list the CJ product library.
     *
     * Supported filters (CJ names): pageNum, pageSize, categoryId,
     * productNameEn, countryCode, startSellPrice, endSellPrice.
     */
    public function products(array $filters = []): array
    {
        return $this->get('product/list', array_filter(
            $filters,
            fn ($value) => $value !== null && $value !== '',
        ));
    }

    /** Fetch a single product by its CJ product id (pid). */
    public function product(string $pid): array
    {
        return $this->get('product/query', ['pid' => $pid]);
    }

    /**
     * Products the merchant added to their own CJ panel ("My Products"),
     * paginated. Each item has productId (pid), vid, nameEn, sku, bigImage,
     * sellPrice, weight, ...
     */
    public function myProducts(int $pageNum = 1, int $pageSize = 50): array
    {
        return $this->get('product/myProduct/query', [
            'pageNum' => $pageNum,
            'pageSize' => $pageSize,
        ]);
    }

    /**
     * Import every product in the merchant's CJ panel into the local catalogue
     * (idempotent on cj_product_id). Detail fields not present on `products`
     * go to dropshipping_product_details.
     *
     * @param  bool  $translate  Machine-translate titles into az/ru/tr.
     * @return array{fetched:int,created:int,updated:int,skipped:int,images:int,map:array<string,int>}
     */
    public function sync(bool $translate = false): array
    {
        $userId = $this->adminUserId();
        $items = $this->allMyProducts();

        $created = $updated = $skipped = $images = 0;
        $map = [];

        foreach ($items as $item) {
            $pid = (string) ($item['productId'] ?? $item['pid'] ?? '');

            if ($pid === '') {
                $skipped++;

                continue;
            }

            try {
                $detail = $this->product($pid);
            } catch (Throwable $e) {
                Log::warning('CJ product detail fetch failed', ['pid' => $pid, 'message' => $e->getMessage()]);
                $skipped++;

                continue;
            }

            if ($detail === []) {
                $skipped++;

                continue;
            }

            $existing = DropshippingProductDetail::query()->where('cj_product_id', $pid)->first();
            $product = $existing?->product;
            $attributes = $this->productAttributes($detail, $item, $translate);

            if ($product) {
                $product->update($attributes);
                $updated++;
            } else {
                if (PlanLimits::reached('max_products', 1)) {
                    $skipped++;

                    continue;
                }

                $product = $this->productService->createFromData($attributes + ['user_id' => $userId]);
                $created++;
            }

            if ($product->images()->count() === 0) {
                $images += $this->importImages($product, $detail, $item);
            }

            DropshippingProductDetail::query()->updateOrCreate(
                ['cj_product_id' => $pid],
                $this->detailAttributes($product, $pid, $item, $detail),
            );

            $map[$pid] = $product->id;
        }

        return [
            'fetched' => count($items),
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
            'images' => $images,
            'map' => $map,
        ];
    }

    /** @return array<int,array<string,mixed>> */
    private function allMyProducts(): array
    {
        $all = [];
        $page = 1;

        do {
            $data = $this->myProducts($page, self::PER_PAGE);
            $content = $data['content'] ?? [];
            $all = array_merge($all, $content);
            $totalPages = (int) ($data['totalPages'] ?? 1);
            $page++;
        } while ($page <= $totalPages && $content !== [] && $page <= self::MAX_PAGES);

        return $all;
    }

    /**
     * Core `products` columns derived from the CJ detail + list item.
     *
     * @return array<string,mixed>
     */
    private function productAttributes(array $detail, array $item, bool $translate): array
    {
        $title = $detail['productNameEn'] ?? $item['nameEn'] ?? null;
        $description = $detail['description'] ?? null;

        $price = $this->priceFromDetail($detail);
        $markup = (float) config('cjdropshopping.price_markup_percent', 0);

        if ($markup > 0) {
            $price = round($price * (1 + $markup / 100), 2);
        }

        $discount = is_numeric($item['discountPrice'] ?? null) ? (float) $item['discountPrice'] : null;

        if ($discount !== null && ($price <= 0 || $discount >= $price)) {
            $discount = null;
        }

        $weight = is_numeric($detail['productWeight'] ?? null) ? round(((float) $detail['productWeight']) / 1000, 3) : null;

        return array_filter([
            'title' => $this->localized($title, $translate),
            // Translating HTML descriptions through Google mangles them, so the
            // English copy is used for every locale.
            'description' => $this->localized($description, false),
            'price' => round($price, 2),
            'discount' => $discount,
            'stock_count' => $this->stockFromDetail($detail),
            'weight' => $weight ?: null,
            'sku' => $detail['productSku'] ?? $item['sku'] ?? null,
            'category_id' => $this->categoryId($detail),
            'is_active' => true,
            'approval_status' => 'approved',
        ], fn ($value) => $value !== null);
    }

    /**
     * @return array<string,mixed>
     */
    private function detailAttributes(Product $product, string $pid, array $item, array $detail): array
    {
        return [
            'product_id' => $product->id,
            'cj_vid' => $item['vid'] ?? null,
            'cj_sku' => $detail['productSku'] ?? $item['sku'] ?? null,
            'cj_category_id' => $detail['categoryId'] ?? null,
            'cj_category_name' => $detail['categoryName'] ?? null,
            'supplier_id' => $detail['supplierId'] ?? null,
            'supplier_name' => $detail['supplierName'] ?? null,
            'product_type' => $detail['productType'] ?? $item['productType'] ?? null,
            'warehouse' => $item['defaultArea'] ?? null,
            'area_country_code' => $item['areaCountryCode'] ?? null,
            'shop_method' => $item['shopMethod'] ?? null,
            'weight_grams' => is_numeric($detail['productWeight'] ?? null) ? (float) $detail['productWeight'] : null,
            'pack_weight_grams' => is_numeric($item['packWeight'] ?? null) ? (float) $item['packWeight'] : null,
            'cj_price' => $detail['sellPrice'] ?? $item['sellPrice'] ?? null,
            'cj_discount_price' => is_numeric($item['discountPrice'] ?? null) ? (float) $item['discountPrice'] : null,
            'listed_num' => (int) ($item['listedShopNum'] ?? 0),
            'sale_status' => isset($item['saleStatus']) ? (string) $item['saleStatus'] : null,
            'is_free_shipping' => (bool) ($item['isFreeShipping'] ?? false),
            'shipping_country_codes' => $item['shippingCountryCodes'] ?? null,
            'variants' => $detail['variants'] ?? null,
            'raw' => $detail,
            'raw_my' => $item,
        ];
    }

    /** Download CJ images into the tenant's public storage and attach them. */
    private function importImages(Product $product, array $detail, array $item): int
    {
        $urls = collect(array_merge(
            [$detail['productImage'] ?? null, $item['bigImage'] ?? null],
            (array) ($detail['productImageSet'] ?? []),
        ))
            ->filter(fn ($url) => is_string($url) && str_starts_with($url, 'http'))
            ->unique()
            ->values();

        $count = 0;

        foreach ($urls as $url) {
            if ($count >= self::MAX_IMAGES) {
                break;
            }

            $path = $this->downloadImage($url);

            if ($path === null) {
                continue;
            }

            $product->images()->create(['image_path' => $path]);
            $count++;
        }

        return $count;
    }

    /** Store a remote image under the tenant's products folder; null on failure. */
    private function downloadImage(string $url): ?string
    {
        try {
            $response = Http::timeout(30)->get($url);

            if (! $response->successful()) {
                return null;
            }

            $extension = strtolower((string) pathinfo((string) parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
            $extension = preg_replace('/[^a-z0-9]/', '', $extension) ?: 'jpg';

            $directory = TenantContext::storagePath('products');
            $path = $directory.'/cj-'.Str::uuid().'.'.$extension;

            Storage::disk('public')->put($path, $response->body());

            return $path;
        } catch (Throwable $e) {
            Log::warning('CJ product image download failed', ['url' => $url, 'message' => $e->getMessage()]);

            return null;
        }
    }

    private function priceFromDetail(array $detail): float
    {
        $variantPrices = collect($detail['variants'] ?? [])
            ->pluck('variantSellPrice')
            ->filter(fn ($price) => is_numeric($price))
            ->map(fn ($price) => (float) $price);

        if ($variantPrices->isNotEmpty()) {
            return (float) $variantPrices->min();
        }

        return $this->minPrice((string) ($detail['sellPrice'] ?? ''));
    }

    /** Parse a CJ price range like "0.64-2.36" or "16.49 -- 83.68". */
    private function minPrice(string $range): float
    {
        preg_match_all('/\d+(?:\.\d+)?/', $range, $matches);

        if ($matches[0] === []) {
            return 0.0;
        }

        return (float) min(array_map('floatval', $matches[0]));
    }

    private function stockFromDetail(array $detail): int
    {
        return (int) collect($detail['variants'] ?? [])
            ->sum(fn ($variant) => (int) ($variant['inventoryNum'] ?? 0));
    }

    private function categoryId(array $detail): ?int
    {
        $cjCategoryId = $detail['categoryId'] ?? null;

        if ($cjCategoryId === null || $cjCategoryId === '') {
            return null;
        }

        $id = Category::query()->where('cj_category_id', (string) $cjCategoryId)->value('id');

        return $id ? (int) $id : null;
    }

    /** @return array<string,string>|null */
    private function localized(?string $source, bool $translate): ?array
    {
        $source = trim((string) $source);

        if ($source === '') {
            return null;
        }

        $name = ['en' => $source];

        foreach (['az', 'ru', 'tr'] as $locale) {
            $name[$locale] = $translate ? $this->translation($source, $locale) : $source;
        }

        return $name;
    }

    private function translation(string $text, string $locale): string
    {
        $key = $locale.'|'.$text;

        return $this->translations[$key] ??= Translate::translate($text, $locale, 'en');
    }

    /**
     * Owner of imported products: the signed-in admin, else an admin account,
     * else any user. products.user_id is required.
     */
    private function adminUserId(): int
    {
        $id = Auth::id() ?? auth('admin')->id();

        if ($id) {
            return (int) $id;
        }

        try {
            $id = User::role('admin')->value('id');
        } catch (Throwable) {
            // roles table not migrated (e.g. minimal setup) — fall through.
            $id = null;
        }

        $id ??= User::query()->value('id');

        if (! $id) {
            throw new RuntimeException(__('No user available to own imported products.'));
        }

        return (int) $id;
    }
}
