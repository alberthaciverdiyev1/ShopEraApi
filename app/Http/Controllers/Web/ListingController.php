<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\StoreListingRequest;
use App\Core\Concerns\InteractsWithModuleControllers;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Modules\Brand\Http\Controllers\BrandController;
use Modules\Delivery\Http\Controllers\DeliveryController;
use Modules\Filter\Http\Controllers\FilterController;
use Modules\Marketplace\Http\Controllers\ListingController as ModuleListingController;
use Modules\Marketplace\Http\Requests\StoreListingRequest as ModuleStoreListingRequest;

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
        $categoryId = (int) request()->query('category', 0);

        $path = $categoryId ? $this->categoryPath($tree, $categoryId) : [];
        $selected = $path ? end($path) : null;
        $children = $selected ? ($selected['children'] ?? []) : $tree;
        $isLeaf = $selected !== null && count($children) === 0;

        $module = app(ModuleListingController::class);

        return view('storefront.listings.create', [
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

        $result = (array) $this->payload(
            app(ModuleListingController::class)->store($form)
        );

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

    public function destroy(int $id): RedirectResponse
    {
        app(ModuleListingController::class)->destroy($this->moduleHttpRequest([], 'DELETE'), $id);

        return back()->with('status', 'Elan silindi.');
    }

    public function renew(int $id): RedirectResponse
    {
        app(ModuleListingController::class)->renew($this->moduleHttpRequest([], 'POST'), $id);

        return back()->with('status', 'Elan yenidən dərc edildi.');
    }

    public function promote(Request $request, int $id): RedirectResponse
    {
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
