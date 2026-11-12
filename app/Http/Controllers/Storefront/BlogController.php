<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Services\Storefront\BlogContentService;
use App\Services\Storefront\CatalogService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BlogController extends Controller
{
    public function __construct(
        private readonly BlogContentService $blogs,
        private readonly CatalogService $catalog,
    ) {}

    public function index(Request $request): View
    {
        app()->setLocale('az');

        $result = $this->blogs->index($request->query());

        return view('storefront.blog.index', [
            'items' => $result['items'],
            'meta' => $result['meta'],
            'blogCategories' => $result['categories'],
            'query' => $request->query(),
            'categories' => $this->catalog->categories(),
        ]);
    }

    public function show(string $slug): View
    {
        app()->setLocale('az');

        $article = $this->blogs->find($slug);

        abort_if($article === null, 404);

        return view('storefront.blog.show', [
            'article' => $article,
            'blogCategories' => $this->blogs->categories(),
            'categories' => $this->catalog->categories(),
        ]);
    }
}
