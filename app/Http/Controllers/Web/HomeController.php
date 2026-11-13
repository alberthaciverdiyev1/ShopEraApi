<?php

namespace App\Http\Controllers\Web;

use App\Core\Concerns\InteractsWithModuleControllers;
use App\Http\Controllers\Controller;
use Modules\Category\Http\Controllers\CategoryController;
use Modules\Popup\Http\Controllers\PopupController;
use Modules\Product\Http\Controllers\ProductController;

class HomeController extends Controller
{
    use InteractsWithModuleControllers;

    public function index()
    {
        // Storefront content is authored in Azerbaijani; the API keeps the
        // request-driven locale, Blade pages default to the site language.
        app()->setLocale('az');

        $popup = $this->payload(app(PopupController::class)->showOne());
        $categories = $this->storefrontCategories();

        return view('storefront.home', [
            'categories' => $categories,
            'categorySections' => $this->homeCategorySections($categories),
            'featuredProducts' => $this->payload(
                app(ProductController::class)->list(
                    $this->moduleHttpRequest(['featured' => 1, 'per_page' => 12])
                )
            ),
            'premiumListings' => $this->payload(
                app(ProductController::class)->list(
                    $this->moduleHttpRequest([
                        'placement' => 'premium',
                        'order_by' => 'created_at',
                        'order_type' => 'desc',
                        'per_page' => 10,
                    ])
                )
            ),
            'popup' => is_array($popup) && $popup !== []
                ? [
                    'type' => $popup['type'] ?? null,
                    'image' => $popup['image'] ?? null,
                    'video' => $popup['video'] ?? null,
                ]
                : null,
        ]);
    }

    /**
     * One row per root category: its name, how many listings it holds and its
     * newest listings. Categories without listings are skipped so the home
     * page never shows an empty block.
     */
    private function homeCategorySections(array $categories): array
    {
        $counts = [];
        $collectCounts = function (array $list) use (&$collectCounts, &$counts): void {
            foreach ($list as $category) {
                $counts[(int) ($category['id'] ?? 0)] = (int) ($category['products_count'] ?? 0);

                if (! empty($category['children'])) {
                    $collectCounts($category['children']);
                }
            }
        };
        $collectCounts($categories);

        $rows = $this->payload(
            app(CategoryController::class)->listWithProducts($this->moduleHttpRequest())
        );

        if (! is_array($rows)) {
            return [];
        }

        $sections = [];

        foreach ($rows as $row) {
            $products = array_values(array_filter((array) ($row['products'] ?? [])));

            if ($products === []) {
                continue;
            }

            $sections[] = [
                'id' => (int) ($row['id'] ?? 0),
                'name' => $row['name'] ?? '',
                'slug' => $row['slug'] ?? null,
                'count' => $counts[(int) ($row['id'] ?? 0)] ?? count($products),
                'products' => array_slice($products, 0, 10),
            ];
        }

        return $sections;
    }
}
