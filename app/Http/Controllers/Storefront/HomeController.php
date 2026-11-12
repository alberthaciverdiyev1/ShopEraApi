<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Services\Storefront\HomeService;

class HomeController extends Controller
{
    public function __construct(private readonly HomeService $home) {}

    public function index()
    {
        // Storefront content is authored in Azerbaijani; the API keeps the
        // request-driven locale, Blade pages default to the site language.
        app()->setLocale('az');

        return view('storefront.home', [
            'categories' => $this->home->categories(),
            'featuredProducts' => $this->home->featuredProducts(),
            'premiumListings' => $this->home->premiumListings(),
            'popup' => $this->home->popup(),
        ]);
    }
}
