<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\StoreListingRequest;
use App\Services\Storefront\SellerService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ListingController extends Controller
{
    public function __construct(private readonly SellerService $seller) {}

    public function index(): View
    {
        return view('storefront.listings.index', [
            'listings' => $this->seller->mine(request()->user()),
            'packages' => $this->seller->packages(),
        ]);
    }

    public function create(): View
    {
        $tree = $this->seller->categoryTree();
        $categoryId = (int) request()->query('category', 0);

        $path = $categoryId ? $this->seller->categoryPath($categoryId) : [];
        $selected = $path ? end($path) : null;
        $children = $selected ? $this->children($selected) : $tree;
        $isLeaf = $selected !== null && count($children) === 0;

        return view('storefront.listings.create', [
            'path' => $path,
            'selected' => $selected,
            'children' => $children,
            'isLeaf' => $isLeaf,
            'schema' => $isLeaf ? $this->seller->fields((int) $selected['id']) : [],
            'filters' => $isLeaf ? $this->seller->filterTree((int) $selected['id']) : [],
            'cities' => $isLeaf ? $this->seller->cities() : [],
            'brands' => $isLeaf && ! empty($selected['needs_brand']) ? $this->seller->brands() : [],
            'published' => session('published'),
        ]);
    }

    public function store(StoreListingRequest $request): RedirectResponse
    {
        $result = $this->seller->create($request->validated(), $request->user(), $request);

        if (! empty($result['manage_url'])) {
            return redirect()
                ->route('storefront.listings.create')
                ->with('published', [
                    'title' => $result['listing']->title['az'] ?? null,
                    'manage_url' => $result['manage_url'],
                ]);
        }

        return redirect()
            ->route('storefront.listings.index')
            ->with('status', 'Elan uğurla yerləşdirildi.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $this->seller->delete($id, request()->user());

        return back()->with('status', 'Elan silindi.');
    }

    public function renew(int $id): RedirectResponse
    {
        $this->seller->renew($id, request()->user());

        return back()->with('status', 'Elan yenidən dərc edildi.');
    }

    public function promote(Request $request, int $id): RedirectResponse
    {
        $data = $request->validate([
            'package_id' => ['required', 'exists:promotion_packages,id'],
        ]);

        $this->seller->promote($id, $request->user(), (int) $data['package_id']);

        return back()->with('status', 'Promosiya sifarişi yaradıldı. Ödəniş təsdiqləndikdən sonra irəli çəkiləcək.');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function children(array $category): array
    {
        $children = $category['children'] ?? [];

        if ($children instanceof ResourceCollection) {
            $children = $children->resolve();
        }

        return (array) $children;
    }
}
