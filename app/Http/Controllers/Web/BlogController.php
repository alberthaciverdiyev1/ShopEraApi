<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Support\Concerns\InteractsWithModuleControllers;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Blog\Http\Controllers\BlogController as ModuleBlogController;

class BlogController extends Controller
{
    use InteractsWithModuleControllers;

    public function index(Request $request): View
    {
        app()->setLocale('az');

        $payload = $this->payload(
            app(ModuleBlogController::class)->list(
                $this->moduleHttpRequest(array_filter([
                    'page' => $request->query('page', 1),
                    'per_page' => 9,
                    'category' => $request->query('category'),
                    'search' => $request->query('search'),
                ]))
            )
        );

        return view('storefront.blog.index', [
            'items' => $payload['items'] ?? [],
            'meta' => $payload['meta'] ?? [
                'current_page' => 1,
                'last_page' => 1,
                'per_page' => 9,
                'total' => 0,
            ],
            'blogCategories' => $payload['sidebar']['categories'] ?? [],
            'query' => $request->query(),
            'categories' => $this->storefrontCategories(),
        ]);
    }

    public function show(string $slug): View
    {
        app()->setLocale('az');

        $article = $this->payload(app(ModuleBlogController::class)->details($slug));

        abort_if(! is_array($article) || $article === [], 404);

        return view('storefront.blog.show', [
            'article' => $article,
            'blogCategories' => $this->payload(app(ModuleBlogController::class)->categories()),
            'categories' => $this->storefrontCategories(),
        ]);
    }
}
