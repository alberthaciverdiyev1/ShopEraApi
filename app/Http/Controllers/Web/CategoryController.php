<?php

namespace App\Http\Controllers\Web;

use App\Core\Concerns\InteractsWithModuleControllers;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class CategoryController extends Controller
{
    use InteractsWithModuleControllers;

    public function index(Request $request): View|RedirectResponse
    {
        app()->setLocale('az');

        $categories = $this->storefrontCategories();
        $slug = trim((string) $request->query('category', ''));

        if ($slug === '') {
            return view('storefront.categories.index', [
                'categories' => $categories,
                'currentCategory' => null,
                'parentCategory' => null,
            ]);
        }

        [$category, $parent] = $this->findCategory($categories, $slug);

        // Unknown slug: fall back to the whole catalogue instead of a 404.
        if ($category === null) {
            return redirect()->route('storefront.categories');
        }

        $children = array_values((array) ($category['children'] ?? []));

        // A leaf category has nothing to drill into, so it goes straight to
        // its listings.
        if ($children === []) {
            return redirect()->to('/shop?category='.urlencode((string) ($category['slug'] ?? $category['id'])));
        }

        return view('storefront.categories.index', [
            'categories' => $children,
            'currentCategory' => $category,
            'parentCategory' => $parent,
        ]);
    }

    /**
     * Walk the nested category tree and return the matching node with its
     * parent (null for a root category).
     *
     * @return array{0: ?array, 1: ?array}
     */
    private function findCategory(array $categories, string $slug, ?array $parent = null): array
    {
        foreach ($categories as $category) {
            $category = (array) $category;

            if ((string) ($category['slug'] ?? '') === $slug) {
                return [$category, $parent];
            }

            $children = $category['children'] ?? [];

            if ($children instanceof Collection) {
                $children = $children->resolve();
            }

            if (! empty($children)) {
                [$found, $foundParent] = $this->findCategory((array) $children, $slug, $category);

                if ($found !== null) {
                    return [$found, $foundParent];
                }
            }
        }

        return [null, null];
    }
}
