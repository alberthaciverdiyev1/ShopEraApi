<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Services\Storefront\CatalogService;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function __construct(private readonly CatalogService $catalog) {}

    public function index(): View
    {
        app()->setLocale('az');

        return view('storefront.categories.index', [
            'categories' => $this->catalog->categories(),
        ]);
    }
}
