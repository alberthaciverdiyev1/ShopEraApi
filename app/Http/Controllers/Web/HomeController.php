<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Core\Concerns\InteractsWithModuleControllers;
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

        return view('storefront.home', [
            'categories' => $this->storefrontCategories(),
            'featuredProducts' => $this->payload(
                app(ProductController::class)->list(
                    $this->moduleHttpRequest(['featured' => 1, 'per_page' => 12])
                )
            ),
            'premiumListings' => $this->payload(
                app(ProductController::class)->list(
                    $this->moduleHttpRequest([
                        'placement' => 'premium,vip',
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
}
