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
use Modules\Brand\Entities\Brand;
use Modules\Category\Entities\Category;
use Modules\CjDropShopping\Entities\DropshippingProductDetail;
use Modules\Color\Entities\Color;
use Modules\Filter\Entities\CategoryFilter;
use Modules\Filter\Entities\Filter;
use Modules\Filter\Entities\ProductFilter;
use Modules\Product\Entities\Product;
use Modules\Product\Services\ProductService;
use Modules\Size\Entities\Size;
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

    /** @var array<string,int>|null lazily loaded lowercased name => id maps */
    private ?array $colorMap = null;

    private ?array $sizeMap = null;

    private ?array $filterMap = null;

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

        $created = $updated = $skipped = $images = $colors = $sizes = $specs = 0;
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
                $product->update($this->updatableAttributes($attributes));
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

            $variantSummary = $this->syncVariants($product, $detail);
            $colors += $variantSummary['colors'];
            $sizes += $variantSummary['sizes'];

            $stock = $this->importStock($detail);

            if ($stock !== null) {
                $product->update(['stock_count' => $stock]);
            }

            $specs += $this->syncSpecifications($product, $detail, $translate);

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
            'colors' => $colors,
            'sizes' => $sizes,
            'specs' => $specs,
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
            'brand_id' => $this->brandId($detail['supplierName'] ?? null),
            'is_active' => true,
            'approval_status' => 'approved',
        ], fn ($value) => $value !== null);
    }

    /**
     * Fields actually written on re-sync. Anything in the protected list is
     * left untouched so locally edited values (price, discount, ...) survive.
     *
     * @param  array<string,mixed>  $attributes
     * @return array<string,mixed>
     */
    private function updatableAttributes(array $attributes): array
    {
        $protected = (array) config('cjdropshopping.import.protected_fields', []);

        return array_diff_key($attributes, array_flip($protected));
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

    /**
     * Parse CJ variants into colour / size options and attach them to the
     * product (creating Colour/Size rows on demand).
     *
     * @return array{colors:int,sizes:int}
     */
    private function syncVariants(Product $product, array $detail): array
    {
        $autoColors = (bool) config('cjdropshopping.variant.auto_colors', true);
        $autoSizes = (bool) config('cjdropshopping.variant.auto_sizes', true);

        if (! $autoColors && ! $autoSizes) {
            return ['colors' => 0, 'sizes' => 0];
        }

        $stopwords = $this->colorStopwords($product, $detail);
        $colors = [];
        $sizes = [];

        foreach (($detail['variants'] ?? []) as $variant) {
            if (! is_array($variant)) {
                continue;
            }

            $parsed = $this->parseVariant($variant, $stopwords);

            if ($autoColors && $parsed['color'] !== null) {
                $colorId = $this->colorId($parsed['color']);

                if ($colorId) {
                    $colors[$colorId] = true;
                }
            }

            if ($autoSizes && $parsed['size'] !== null) {
                $sizeId = $this->sizeId($parsed['size']);

                if ($sizeId) {
                    $sizes[$sizeId] = [
                        'price' => is_numeric($variant['variantSellPrice'] ?? null) ? (float) $variant['variantSellPrice'] : null,
                        'wholesale_price' => null,
                        'discount' => null,
                    ];
                }
            }
        }

        // Replace (even with an empty set) so stale/wrong options imported by an
        // earlier run are removed when the provider no longer reports them.
        $product->colors()->sync(array_keys($colors));
        $product->sizes()->sync($sizes);

        return ['colors' => count($colors), 'sizes' => count($sizes)];
    }

    /**
     * Colour stopwords for one product: the shared config list plus every word
     * taken from the product's own title and category, so a new catalogue's own
     * vocabulary never turns into a bogus colour.
     *
     * @return array<int,string>
     */
    private function colorStopwords(Product $product, array $detail): array
    {
        $static = (array) config('cjdropshopping.variant.color_stopwords', []);

        $text = implode(' ', array_filter([
            implode(' ', array_map('strval', (array) $product->getTranslations('title'))),
            $detail['productNameEn'] ?? null,
            $detail['categoryName'] ?? null,
            $detail['twoCategoryName'] ?? null,
            $detail['oneCategoryName'] ?? null,
        ]));

        $allowlist = array_map('strtolower', (array) config('cjdropshopping.variant.color_allowlist', []));

        $dynamic = array_filter(
            preg_split('/[^a-z0-9]+/i', Str::lower($text)) ?: [],
            fn ($token) => strlen($token) >= 3 && ! in_array($token, $allowlist, true),
        );

        return array_values(array_unique(array_map('strtolower', array_merge($static, $dynamic))));
    }

    /**
     * Best-effort extraction of colour/size from a CJ variant. CJ has no
     * dedicated colour/size fields — only a free-text variantKey like
     * "Washed Hoodie Black-S", "SB102063-M" or "Black".
     *
     * @return array{color:?string,size:?string}
     */
    private function parseVariant(array $variant, array $stopwords): array
    {
        $key = trim((string) ($variant['variantKey'] ?? ''));
        $size = null;
        $color = null;

        // Some products carry structured options like "Color:Black;Size:M".
        $property = $variant['variantProperty'] ?? null;

        if (is_string($property) && $property !== '' && $property !== '[]') {
            if (preg_match('/colou?r\s*[:=]\s*([^;,]+)/i', $property, $match)) {
                $color = trim($match[1]);
            }

            if (preg_match('/size\s*[:=]\s*([^;,]+)/i', $property, $match)) {
                $size = trim($match[1]);
            }
        }

        if ($size === null) {
            $size = $this->extractSize($key);

            if ($size !== null) {
                $key = trim((string) preg_replace('/'.preg_quote($size, '/').'$/i', '', $key), ' -,');
            }
        }

        if ($color === null) {
            $color = $this->extractColor($key, $stopwords);
        }

        return ['color' => $color, 'size' => $size];
    }

    private function extractSize(string $text): ?string
    {
        if ($text === '') {
            return null;
        }

        // Letter ranges: "S to M", "XL-XXL".
        if (preg_match('/\b(XS|S|M|L|XL|XXL|XXXL|2XL|3XL|4XL)\s*(?:-|to|–)\s*(XS|S|M|L|XL|XXL|XXXL|2XL|3XL|4XL)\b/i', $text, $match)) {
            return trim($match[1]).' - '.trim($match[2]);
        }

        // Length / capacity sizes: "8inch", "10cm", "128GB", "1TB".
        if (preg_match('/\b(\d+(?:\.\d+)?\s*(?:inch|cm|"|gb|tb))\b/i', $text, $match)) {
            return trim($match[1]);
        }

        // Numeric ranges: "36 to 38", "36-38".
        if (preg_match('/\b([0-9]{1,3}\s*(?:to|-)\s*[0-9]{1,3})\b/i', $text, $match)) {
            return trim($match[1]);
        }

        $sizeTokens = array_map('strtoupper', (array) config('cjdropshopping.variant.size_tokens', []));
        $tokens = array_values(array_filter(preg_split('/[\s\-,]+/', $text) ?: [], fn ($token) => $token !== ''));
        $last = (string) end($tokens);
        $upper = strtoupper($last);

        if ($upper !== '' && in_array($upper, $sizeTokens, true)) {
            return $last;
        }

        // A trailing number is only a size when it stands alone ("38") or is
        // marked as one ("US 8"). "Blue 2" / "Style 1" are pack/style labels,
        // not sizes.
        if ($last !== '' && preg_match('/^\d{1,3}(\.\d)?$/', $last)) {
            $previous = count($tokens) >= 2 ? strtolower($tokens[count($tokens) - 2]) : null;

            if (count($tokens) === 1 || in_array($previous, ['us', 'eu', 'uk', 'size'], true)) {
                return $last;
            }
        }

        return null;
    }

    private function extractColor(string $text, array $stopwords): ?string
    {
        // Drop model codes like "SB102063" / "CJJF3221399".
        $text = (string) preg_replace('/\b[A-Z]{1,5}\d{3,}[A-Z0-9-]*\b/i', ' ', $text);

        $tokens = array_values(array_filter(
            preg_split('/[\s\-,]+/', $text) ?: [],
            fn ($token) => $token !== '' && ! preg_match('/\d/', $token),
        ));

        if ($tokens === []) {
            return null;
        }

        $allowlist = array_map('strtolower', (array) config('cjdropshopping.variant.color_allowlist', []));
        $qualifiers = ['light', 'dark', 'deep', 'pale', 'bright', 'neon', 'hot', 'baby', 'sky', 'royal'];

        // Prefer a recognised colour word (with a leading qualifier), scanning
        // from the end so the most specific colour wins and stray trailing
        // words like "Luokou" are not mistaken for a colour.
        for ($i = count($tokens) - 1; $i >= 0; $i--) {
            if (! in_array(strtolower($tokens[$i]), $allowlist, true)) {
                continue;
            }

            $phrase = [];

            if ($i > 0 && in_array(strtolower($tokens[$i - 1]), $qualifiers, true)) {
                $phrase[] = $tokens[$i - 1];
            }

            $phrase[] = $tokens[$i];

            return Str::title(implode(' ', $phrase));
        }

        // No recognised colour word: leave it out rather than inventing a colour
        // from an arbitrary token (e.g. "Greena", "Style").
        return null;
    }

    private function colorId(string $name): ?int
    {
        $needle = $this->normalizeName($name);

        if ($needle === null) {
            return null;
        }

        $this->colorMap ??= $this->loadTranslatableMap(Color::class);

        if (isset($this->colorMap[$needle])) {
            $this->backfillColorHex($this->colorMap[$needle], $name);

            return $this->colorMap[$needle];
        }

        $color = Color::query()->create([
            'name' => $this->localized($name, false),
            'hex' => $this->colorHex($name),
            'is_active' => true,
        ]);

        return $this->colorMap[$needle] = (int) $color->id;
    }

    /** Fill in a hex value on an existing colour that does not have one yet. */
    private function backfillColorHex(int $id, string $name): void
    {
        $hex = $this->colorHex($name);

        if ($hex === null) {
            return;
        }

        $color = Color::query()->find($id);

        if ($color && empty($color->hex)) {
            $color->update(['hex' => $hex]);
        }
    }

    /** Resolve a hex value for a colour name from config (exact, then base word). */
    private function colorHex(string $name): ?string
    {
        $map = array_change_key_case((array) config('cjdropshopping.variant.color_hex', []), CASE_LOWER);
        $needle = Str::lower(trim($name));

        if (isset($map[$needle])) {
            return $map[$needle];
        }

        foreach (preg_split('/[\s-]+/', $needle) ?: [] as $token) {
            if (isset($map[$token])) {
                return $map[$token];
            }
        }

        return null;
    }

    private function sizeId(string $name): ?int
    {
        $needle = $this->normalizeName($name);

        if ($needle === null) {
            return null;
        }

        $this->sizeMap ??= $this->loadTranslatableMap(Size::class);

        if (isset($this->sizeMap[$needle])) {
            return $this->sizeMap[$needle];
        }

        $size = Size::query()->create(['name' => $this->localized($name, false), 'is_active' => true]);

        return $this->sizeMap[$needle] = (int) $size->id;
    }

    private function normalizeName(string $name): ?string
    {
        $name = Str::lower(trim($name));

        return $name === '' ? null : $name;
    }

    /** @return array<string,int> lowercased translated name => id */
    private function loadTranslatableMap(string $model, string $attribute = 'name'): array
    {
        $map = [];

        foreach ($model::query()->get() as $row) {
            foreach ((array) $row->getTranslations($attribute) as $value) {
                $key = Str::lower(trim((string) $value));

                if ($key !== '' && ! isset($map[$key])) {
                    $map[$key] = (int) $row->id;
                }
            }
        }

        return $map;
    }

    /**
     * Turn the "Key: Value" specification lines from the CJ description into
     * dynamic Filters: a Filter per key (shared catalogue-wide), attached to
     * the product's category, with the product's value on product_filters.
     */
    private function syncSpecifications(Product $product, array $detail, bool $translate): int
    {
        $specs = $this->parseSpecifications($detail);

        if ($specs === []) {
            return 0;
        }

        $count = 0;

        foreach ($specs as $key => $value) {
            $filterId = $this->filterId($key, $translate);

            if ($filterId === null) {
                continue;
            }

            if ($product->category_id) {
                CategoryFilter::query()->firstOrCreate([
                    'filter_id' => $filterId,
                    'category_id' => $product->category_id,
                ]);
            }

            ProductFilter::query()->updateOrCreate(
                ['product_id' => $product->id, 'filter_id' => $filterId],
                ['value' => mb_substr($value, 0, 255)],
            );

            $count++;
        }

        return $count;
    }

    /**
     * Parse "Key: Value" lines out of the CJ description HTML.
     *
     * @return array<string,string>
     */
    private function parseSpecifications(array $detail): array
    {
        $html = (string) ($detail['description'] ?? '');

        if (trim($html) === '') {
            return [];
        }

        $text = html_entity_decode(
            strip_tags((string) preg_replace('/<(br|\/p|\/div|\/li|\/tr|\/h[1-6])\b[^>]*>/i', "\n", $html)),
            ENT_QUOTES | ENT_HTML5,
        );

        $skip = array_map('strtolower', (array) config('cjdropshopping.spec.skip_keys', []));
        $max = (int) config('cjdropshopping.spec.max_keys', 20);
        $specs = [];

        foreach (preg_split('/\r?\n/', $text) ?: [] as $line) {
            $line = trim($line);

            if ($line === '' || count($specs) >= $max) {
                continue;
            }

            if (! preg_match('/^([A-Za-z][A-Za-z0-9 \/°%().\'\-]{1,40}):\s*(\S.{0,200})$/u', $line, $match)) {
                continue;
            }

            $key = trim($match[1]);
            $value = trim($match[2]);

            if (in_array(Str::lower($key), $skip, true)) {
                continue;
            }

            // Reject feature headings / sentences masquerading as specs.
            if (str_word_count($key) > 4 || mb_strlen($value) > 120 || preg_match('/\.\s+/', $value)) {
                continue;
            }

            // First occurrence wins so a "Specification:" block is not
            // overwritten by later, looser mentions.
            $specs[$key] ??= $value;
        }

        return $specs;
    }

    private function filterId(string $title, bool $translate): ?int
    {
        $needle = Str::lower(trim($title));

        if ($needle === '') {
            return null;
        }

        $this->filterMap ??= $this->loadTranslatableMap(Filter::class, 'title');

        if (isset($this->filterMap[$needle])) {
            return $this->filterMap[$needle];
        }

        $filter = Filter::query()->create([
            'title' => $this->localized($title, $translate),
            'type' => 'select',
        ]);

        return $this->filterMap[$needle] = (int) $filter->id;
    }

    private function brandId(?string $name): ?int
    {
        $name = trim((string) $name);

        if ($name === '') {
            return null;
        }

        return (int) Brand::query()->firstOrCreate(['name' => $name], ['is_active' => true])->id;
    }

    /**
     * Sum the live inventory across a product's variants via the dedicated
     * stock endpoint. Returns null when no variant could be resolved, so an
     * outage never zeroes an existing stock count.
     */
    private function importStock(array $detail): ?int
    {
        $variants = $detail['variants'] ?? [];

        if (! is_array($variants) || $variants === []) {
            return null;
        }

        $total = 0;
        $resolved = false;

        foreach ($variants as $variant) {
            $vid = $variant['vid'] ?? null;

            if (! $vid) {
                continue;
            }

            try {
                $rows = $this->get('product/stock/queryByVid', ['vid' => (string) $vid]);
            } catch (Throwable $e) {
                continue;
            }

            if (! is_array($rows) || $rows === []) {
                continue;
            }

            $resolved = true;

            foreach ($rows as $row) {
                if (is_array($row)) {
                    $total += (int) ($row['totalInventoryNum'] ?? $row['storageNum'] ?? 0);
                }
            }
        }

        return $resolved ? $total : null;
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
