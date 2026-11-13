<?php

namespace App\Http\Controllers\Web;

use App\Core\Concerns\InteractsWithModuleControllers;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\StoreListingRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Modules\Brand\Http\Controllers\BrandController;
use Modules\Color\Http\Controllers\ColorController;
use Modules\Delivery\Http\Controllers\DeliveryController;
use Modules\Filter\Http\Controllers\FilterController;
use Modules\Filter\Http\Requests\ProductFilterValuesRequest;
use Modules\Marketplace\Http\Controllers\ListingController as ModuleListingController;
use Modules\Marketplace\Http\Controllers\VendorController;
use Modules\Marketplace\Http\Requests\StoreListingRequest as ModuleStoreListingRequest;
use Modules\Size\Http\Controllers\SizeController;

class ListingController extends Controller
{
    use InteractsWithModuleControllers;

    public function index(): View
    {
        return view('storefront.listings.index', [
            'listings' => $this->myListings(),
            'packages' => (array) $this->payload(
                app(ModuleListingController::class)->packages()
            ),
        ]);
    }

    public function create(): View
    {
        $tree = $this->storefrontCategories();
        $category = (string) request()->query('category', '');

        // The picker links carry category slugs; ids stay supported.
        if ($category !== '' && ! is_numeric($category)) {
            $categoryId = $this->categoryIdBySlug($category) ?? 0;
        } else {
            $categoryId = (int) $category;
        }

        $path = $categoryId ? $this->categoryPath($tree, $categoryId) : [];
        $selected = $path ? end($path) : null;
        $children = $selected ? ($selected['children'] ?? []) : $tree;
        $isLeaf = $selected !== null && count($children) === 0;

        $module = app(ModuleListingController::class);

        // Accounts that own a store (or work in one) pick the seller at
        // publish time: the store or themselves.
        $vendor = auth()->check()
            ? $this->payload(app(VendorController::class)->mine($this->moduleHttpRequest()))
            : null;
        $vendorName = is_array($vendor) ? ($vendor['name'] ?? null) : null;
        $canPublishAsStore = $vendorName !== null
            && in_array('listings', $vendor['permissions'] ?? [], true);

        return view('storefront.listings.create', [
            'publishAsStore' => $canPublishAsStore ? $vendorName : null,
            'path' => $path,
            'selected' => $selected,
            'children' => $children,
            'isLeaf' => $isLeaf,
            'schema' => $isLeaf
                ? (array) $this->payload($module->fields($this->moduleHttpRequest(['category_id' => $selected['id']])))
                : [],
            'filters' => $isLeaf
                ? (array) $this->payload(
                    app(FilterController::class)->tree($this->moduleHttpRequest(['category_id' => $selected['id']]))
                )
                : [],
            'cities' => $isLeaf
                ? (array) $this->payload(app(DeliveryController::class)->cities())
                : [],
            'brands' => $isLeaf && ! empty($selected['needs_brand'])
                ? (array) $this->payload(app(BrandController::class)->list($this->moduleHttpRequest()))
                : [],
            'colors' => $isLeaf
                ? (array) $this->payload(app(ColorController::class)->list($this->moduleHttpRequest(['per_page' => 200])))
                : [],
            'sizes' => $isLeaf
                ? (array) $this->payload(app(SizeController::class)->list($this->moduleHttpRequest(['per_page' => 200])))
                : [],
            'published' => session('published'),
        ]);
    }

    public function store(StoreListingRequest $request): RedirectResponse
    {
        $form = $this->moduleRequest(
            ModuleStoreListingRequest::class,
            $request->validated(),
            'POST',
            $request->allFiles()
        );

        $module = app(ModuleListingController::class);

        // Guests get the secret management link; signed-in owners keep the
        // regular listing that lives in their account.
        $result = (array) $this->payload(
            auth()->check() ? $module->store($form) : $module->storeGuest($form)
        );

        $this->storeFilters($request, (int) data_get($result, 'listing.id'));

        if (! empty($result['manage_url'])) {
            $title = data_get($result, 'listing.title');
            $title = is_array($title) ? ($title['az'] ?? reset($title)) : $title;

            return redirect()
                ->route('storefront.listings.create')
                ->with('published', [
                    'title' => $title ?: null,
                    'manage_url' => $result['manage_url'],
                ]);
        }

        return redirect()
            ->route('storefront.listings.index')
            ->with('status', 'Elan uğurla yerləşdirildi.');
    }

    /**
     * Persist the filter choices of a freshly created listing. Choices are
     * stored in product_filters (filter_id + option label), the table the shop
     * sidebar reads; only options the category actually offers are kept.
     */
    private function storeFilters(Request $request, int $listingId): void
    {
        $selected = (array) $request->input('filters', []);

        if ($listingId <= 0 || $selected === []) {
            return;
        }

        $allowed = [];

        foreach ((array) $this->payload(app(FilterController::class)->tree(
            $this->moduleHttpRequest(['category_id' => $request->integer('category_id')])
        )) as $filter) {
            $allowed[(int) $filter['id']] = array_map('strval', (array) ($filter['options'] ?? []));
        }

        $values = [];

        foreach ($selected as $filterId => $choices) {
            $filterId = (int) $filterId;

            if (! isset($allowed[$filterId])) {
                continue;
            }

            foreach ((array) $choices as $choice) {
                $choice = trim((string) $choice);

                if ($choice !== '' && in_array($choice, $allowed[$filterId], true)) {
                    $values[] = ['filter_id' => $filterId, 'value' => $choice];
                }
            }
        }

        if ($values === []) {
            return;
        }

        app(FilterController::class)->setProductValues(
            $this->moduleRequest(ProductFilterValuesRequest::class, [
                'product_id' => $listingId,
                'values' => $values,
            ])
        );
    }

    public function destroy(string $slug): RedirectResponse
    {
        $id = $this->listingIdBySlug($slug);

        app(ModuleListingController::class)->destroy($this->moduleHttpRequest([], 'DELETE'), $id);

        return back()->with('status', 'Elan silindi.');
    }

    public function renew(string $slug): RedirectResponse
    {
        $id = $this->listingIdBySlug($slug);

        app(ModuleListingController::class)->renew($this->moduleHttpRequest([], 'POST'), $id);

        return back()->with('status', 'Elan yenidən dərc edildi.');
    }

    public function promote(Request $request, string $slug): RedirectResponse
    {
        $id = $this->listingIdBySlug($slug);

        $data = $request->validate([
            'package_id' => ['required', 'exists:promotion_packages,id'],
        ]);

        app(ModuleListingController::class)->promote(
            $this->moduleHttpRequest($data, 'POST'),
            $id
        );

        return back()->with('status', 'Promosiya sifarişi yaradıldı. Ödəniş təsdiqləndikdən sonra irəli çəkiləcək.');
    }

    /**
     * Listings are addressed by slug in the storefront; resolve it to the id
     * the marketplace module works with.
     */
    private function listingIdBySlug(string $slug): int
    {
        $id = $this->productIdBySlug($slug);
        abort_if($id === null, 404);

        return $id;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function myListings(): array
    {
        $result = app(ModuleListingController::class)->myListings($this->moduleHttpRequest());

        if ($result instanceof ResourceCollection) {
            return $result->resolve();
        }

        return (array) $this->payload($result);
    }

    /**
     * Walk the category tree and return the ancestor chain down to $id.
     *
     * @return array<int, array<string, mixed>>
     */
    private function categoryPath(array $nodes, int $id): array
    {
        foreach ($nodes as $node) {
            $path = [$node];

            if ((int) $node['id'] === $id) {
                return $path;
            }

            $found = $this->categoryPath($node['children'] ?? [], $id);

            if ($found !== []) {
                return array_merge($path, $found);
            }
        }

        return [];
    }
}
