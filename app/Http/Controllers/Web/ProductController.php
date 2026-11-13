<?php

namespace App\Http\Controllers\Web;

use App\Core\Concerns\InteractsWithModuleControllers;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Marketplace\Http\Controllers\ListingController;
use Modules\Product\Http\Controllers\ProductController as ModuleProductController;
use Modules\Product\Http\Controllers\ReviewController;
use Modules\Product\Http\Requests\ReviewAddRequest;

class ProductController extends Controller
{
    use InteractsWithModuleControllers;

    private const CORE_KEYS = ['brend', 'brand', 'kateqoriya', 'category', 'üslub', 'uslub', 'style', 'məqsəd', 'meqsed', 'purpose'];

    public function show(Request $request)
    {
        $id = (int) $request->query('id');

        if ($id <= 0) {
            abort(404);
        }

        $product = $this->productById($id);
        abort_if($product === null, 404);

        // Public URLs carry slugs only: old id links redirect to the slug page.
        if (! empty($product['slug'])) {
            return redirect('/elan/'.$product['slug'], 301);
        }

        return $this->render($product);
    }

    public function showBySlug(string $slug)
    {
        return $this->render($this->productBySlug($slug));
    }

    /**
     * Seller contact is public in the API too; the Blade page reveals it with
     * a same-origin request so the number is never written into the HTML.
     */
    public function contact(string $slug)
    {
        $product = $this->productBySlug($slug);

        abort_if($product === null, 404);

        return response()->json(
            $this->payload(app(ListingController::class)->contact($product['id']))
        );
    }

    /**
     * Store a review written on the product page through the module controller
     * and its ReviewAddRequest, so validation rules stay identical to the API.
     */
    public function review(string $slug, Request $request)
    {
        $product = $this->productBySlug($slug);

        abort_if($product === null, 404);

        $form = $this->moduleRequest(ReviewAddRequest::class, [
            'product_id' => $product['id'],
            'rate' => $request->input('rate'),
            'comment' => $request->input('comment'),
        ], 'POST', $request->allFiles());

        $response = app(ReviewController::class)->add($form);
        $payload = $response->getData(true);

        if (! ($payload['success'] ?? false)) {
            return back()->withErrors(['comment' => $payload['message'] ?? 'Rəy göndərilə bilmədi.']);
        }

        return back()->with('review_status', 'Rəyiniz təsdiq üçün göndərildi. Təşəkkürlər!');
    }

    private function render(?array $product)
    {
        abort_if($product === null, 404);

        app()->setLocale('az');

        return view('storefront.product.show', [
            'product' => $product,
            'similar' => $this->similar($product),
            'categories' => $this->storefrontCategories(),
        ]);
    }

    private function productById(int $id): ?array
    {
        $response = app(ModuleProductController::class)->details($id);
        $payload = $response->getData(true);

        if (empty($payload['success']) || empty($payload['data'])) {
            return null;
        }

        return $this->present($payload['data']);
    }

    private function productBySlug(string $slug): ?array
    {
        $response = app(ModuleProductController::class)->detailsBySlug($slug);
        $payload = $response->getData(true);

        if (empty($payload['success']) || empty($payload['data'])) {
            return null;
        }

        return $this->present($payload['data']);
    }

    /**
     * Add the values the Blade view renders: gallery, money, specs and the
     * category/brand characteristics block.
     */
    private function present(array $product): array
    {
        $images = collect($product['images'] ?? [])
            ->pluck('image_path')
            ->filter()
            ->values()
            ->all();

        if ($images === []) {
            $images = ['/assets/images/top-deals-item/topDealsItemThumb1_1.png'];
        }

        $price = (float) ($product['price'] ?? 0);
        $salePrice = (float) ($product['discount'] ?? 0);
        $hasDiscount = $salePrice > 0 && $salePrice < $price;
        $currentPrice = $hasDiscount ? $salePrice : $price;
        $discountPercent = ($hasDiscount && $price > 0)
            ? (int) round((($price - $salePrice) / $price) * 100)
            : 0;

        $specs = $this->specs($product);
        $characteristics = $this->characteristics($product);

        return $product + [
            'gallery' => $images,
            'current_price' => $currentPrice,
            'original_price' => $price,
            'has_discount' => $hasDiscount,
            'discount_percent' => $discountPercent,
            'product_code' => ($product['sku'] ?? null) ?: str_pad((string) $product['id'], 6, '0', STR_PAD_LEFT),
            'specs' => $specs,
            'characteristics' => $characteristics,
            'details' => array_merge($specs, $characteristics),
            'reviews' => $product['reviews'] ?? [],
            'review_count' => count($product['reviews'] ?? []) ?: (int) ($product['rate_count'] ?? 0),
            'category_path' => $product['category_path'] ?? [],
        ];
    }

    /**
     * Listing filters chosen when the product was posted (brand/model/…).
     */
    private function specs(array $product): array
    {
        $listingFilters = collect($product['filter_values'] ?? [])
            ->filter(fn ($value) => ! empty($value['filter']) && ! empty($value['value']))
            ->map(fn ($value) => ['label' => (string) $value['filter'], 'value' => (string) $value['value']])
            ->values()
            ->all();

        if ($listingFilters !== []) {
            return $listingFilters;
        }

        $filters = collect($product['filters'] ?? [])
            ->filter(fn ($filter) => ! in_array(mb_strtolower(trim((string) ($filter['name'] ?? ''))), self::CORE_KEYS, true))
            ->map(fn ($filter) => [
                'label' => (string) ($filter['name'] ?? $filter['title'] ?? 'Parametr'),
                'value' => (string) ($filter['value'] ?? ''),
            ])
            ->filter(fn ($filter) => $filter['value'] !== '')
            ->values()
            ->all();

        return $filters;
    }

    /**
     * Brand, category trail, city and style — the always-on characteristics.
     */
    private function characteristics(array $product): array
    {
        $list = [];

        if (! empty($product['brand']['name'])) {
            $list[] = [
                'label' => 'Brend',
                'value' => $product['brand']['name'],
                'link' => ! empty($product['brand']['slug']) ? '/shop?brand='.$product['brand']['slug'] : null,
            ];
        }

        $path = collect($product['category_path'] ?? [])->filter(fn ($category) => ! empty($category['name']))->values();

        if ($path->count() >= 2) {
            $parent = $path[$path->count() - 2];
            $leaf = $path[$path->count() - 1];

            $list[] = [
                'label' => 'Kateqoriya',
                'value' => $parent['name'],
                'link' => '/shop?category='.($parent['slug'] ?? $parent['id']),
            ];
            $list[] = [
                'label' => 'Alt kateqoriya',
                'value' => $leaf['name'],
                'link' => '/shop?category='.($leaf['slug'] ?? $leaf['id']),
            ];
        } elseif ($path->count() === 1) {
            $list[] = [
                'label' => 'Kateqoriya',
                'value' => $path[0]['name'],
                'link' => '/shop?category='.($path[0]['slug'] ?? $path[0]['id']),
            ];
        } elseif (! empty($product['category']['name'])) {
            $list[] = [
                'label' => 'Kateqoriya',
                'value' => $product['category']['name'],
                'link' => ! empty($product['category']['id']) ? '/shop?category='.($product['category']['slug'] ?? $product['category']['id']) : null,
            ];
        }

        if (! empty($product['city'])) {
            $list[] = ['label' => 'Şəhər', 'value' => $product['city'], 'link' => null];
        }

        if (! empty($product['gender'])) {
            $list[] = ['label' => 'Üslub', 'value' => $product['gender'], 'link' => null];
        }

        return $list;
    }

    /**
     * Up to five other listings from the same category.
     */
    private function similar(array $product, int $limit = 5): array
    {
        $categoryId = $product['category']['id'] ?? null;

        if (! $categoryId) {
            return [];
        }

        $items = $this->searchProducts(['category' => $categoryId, 'per_page' => $limit + 1])['items'];

        return collect($items)
            ->reject(fn ($item) => (int) $item['id'] === (int) $product['id'])
            ->take($limit)
            ->values()
            ->all();
    }
}
