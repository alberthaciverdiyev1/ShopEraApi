<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Support\Concerns\InteractsWithModuleControllers;
use Illuminate\View\View;

class CategoryController extends Controller
{
    use InteractsWithModuleControllers;

    public function index(): View
    {
        app()->setLocale('az');

        return view('storefront.categories.index', [
            'categories' => $this->storefrontCategories(),
        ]);
    }
}
