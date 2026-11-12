<?php

namespace App\Services\Storefront;

use Illuminate\Http\Request;
use Modules\Blog\Services\BlogService as ModuleBlogService;

/**
 * Blog reads for the Blade pages. Delegates to the module service so the
 * listing, pagination and related-post rules match the API.
 */
class BlogContentService
{
    public function __construct(private readonly ModuleBlogService $blogs) {}

    /**
     * @return array{items: array, meta: array, categories: array}
     */
    public function index(array $query): array
    {
        $payload = $this->blogs->list(
            Request::create('/api/blog', 'GET', array_filter([
                'page' => $query['page'] ?? 1,
                'per_page' => 9,
                'category' => $query['category'] ?? null,
                'search' => $query['search'] ?? null,
            ]))
        )->getData(true);

        return [
            'items' => $payload['data']['items'] ?? [],
            'meta' => $payload['data']['meta'] ?? [
                'current_page' => 1,
                'last_page' => 1,
                'per_page' => 9,
                'total' => 0,
            ],
            'categories' => $payload['data']['sidebar']['categories'] ?? [],
        ];
    }

    public function find(string $slug): ?array
    {
        $payload = $this->blogs->details($slug)->getData(true);

        if (empty($payload['success']) || empty($payload['data'])) {
            return null;
        }

        return $payload['data'];
    }

    /**
     * @return array<int, array{name:string, count:int}>
     */
    public function categories(): array
    {
        $payload = $this->blogs->categories()->getData(true);

        return $payload['data'] ?? [];
    }
}
