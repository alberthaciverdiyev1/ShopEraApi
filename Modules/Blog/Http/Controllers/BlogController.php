<?php

namespace Modules\Blog\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Blog\Services\BlogService;

class BlogController extends Controller
{
    private BlogService $blogService;

    public function __construct(BlogService $blogService)
    {
        $this->blogService = $blogService;
    }

    public function list(Request $request): JsonResponse
    {
        return $this->blogService->list($request);
    }

    public function details(string|int $slugOrId): JsonResponse
    {
        return $this->blogService->details($slugOrId);
    }

    public function recent(Request $request): JsonResponse
    {
        $limit = min(max((int) $request->input('limit', 4), 1), 20);

        return $this->blogService->recent($limit);
    }

    public function categories(): JsonResponse
    {
        return $this->blogService->categories();
    }
}
