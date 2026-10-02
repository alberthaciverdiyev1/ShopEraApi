<?php

namespace Modules\Blog\Services;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Blog\Entities\Blog;
use Modules\Blog\Http\Resources\BlogDetailsResource;
use Modules\Blog\Http\Resources\BlogResource;

class BlogService
{
    private Blog $model;

    public function __construct(Blog $model)
    {
        $this->model = $model;
    }

    public function list(Request $request): JsonResponse
    {
        $query = $this->model::query()->active();

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $locale = app()->getLocale();
            $query->where(function ($q) use ($search, $locale) {
                $q->where("title->{$locale}", 'ILIKE', "%{$search}%")
                    ->orWhere('title->az', 'ILIKE', "%{$search}%")
                    ->orWhere('title->en', 'ILIKE', "%{$search}%")
                    ->orWhere("description->{$locale}", 'ILIKE', "%{$search}%")
                    ->orWhere('slug', 'ILIKE', "%{$search}%");
            });
        }

        if ($request->filled('category')) {
            $query->where('category', $request->input('category'));
        }

        if ($request->filled('tag')) {
            $query->whereJsonContains('tags', $request->input('tag'));
        }

        $perPage = min(max((int) $request->input('per_page', 9), 1), 50);
        $paginator = $query->orderByDesc('published_at')->orderByDesc('id')->paginate($perPage);

        // Sidebar widgets metadata
        $categories = $this->model::query()->active()
            ->select('category', DB::raw('count(*) as count'))
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->groupBy('category')
            ->orderByDesc('count')
            ->get()
            ->map(fn ($item) => [
                'name' => $item->category,
                'count' => (int) $item->count,
            ]);

        $recentPosts = $this->model::query()->active()
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->take(4)
            ->get();

        $allTags = $this->model::query()->active()
            ->whereNotNull('tags')
            ->pluck('tags')
            ->flatten()
            ->filter()
            ->unique()
            ->values()
            ->take(15);

        return responseHelper(__('Blogs retrieved successfully.'), 200, [
            'items' => BlogResource::collection($paginator->getCollection()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
            'sidebar' => [
                'categories' => $categories,
                'recent_posts' => BlogResource::collection($recentPosts),
                'tags' => $allTags,
            ],
        ]);
    }

    public function details(string|int $slugOrId): JsonResponse
    {
        $blog = $this->model::query()->active()
            ->where(function ($q) use ($slugOrId) {
                if (is_numeric($slugOrId)) {
                    $q->where('id', (int) $slugOrId)->orWhere('slug', (string) $slugOrId);
                } else {
                    $q->where('slug', (string) $slugOrId);
                }
            })
            ->first();

        if (! $blog) {
            return responseHelper(__('Blog not found.'), 404, null);
        }

        // Increment view count
        $blog->increment('views');

        // Fetch related posts (same category or latest, excluding current)
        $related = $this->model::query()->active()
            ->where('id', '!=', $blog->id)
            ->when($blog->category, fn ($q) => $q->where('category', $blog->category))
            ->orderByDesc('published_at')
            ->take(3)
            ->get();

        if ($related->count() < 3) {
            $filler = $this->model::query()->active()
                ->where('id', '!=', $blog->id)
                ->whereNotIn('id', $related->pluck('id'))
                ->orderByDesc('published_at')
                ->take(3 - $related->count())
                ->get();
            $related = $related->merge($filler);
        }

        $blog->relatedPosts = $related;

        return responseHelper(__('Blog details retrieved successfully.'), 200, BlogDetailsResource::make($blog));
    }

    public function recent(int $limit = 4): JsonResponse
    {
        $posts = $this->model::query()->active()
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->take($limit)
            ->get();

        return responseHelper(__('Recent blogs retrieved successfully.'), 200, BlogResource::collection($posts));
    }

    public function categories(): JsonResponse
    {
        $categories = $this->model::query()->active()
            ->select('category', DB::raw('count(*) as count'))
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->groupBy('category')
            ->orderByDesc('count')
            ->get()
            ->map(fn ($item) => [
                'name' => $item->category,
                'count' => (int) $item->count,
            ]);

        return responseHelper(__('Blog categories retrieved successfully.'), 200, $categories);
    }
}
