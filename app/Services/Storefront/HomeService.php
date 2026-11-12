<?php

namespace App\Services\Storefront;

use Modules\Category\Entities\Category;
use Modules\Category\Http\Transformers\CategoryResource;
use Modules\Popup\Entities\Popup;
use Modules\Product\Entities\Product;
use Modules\Product\Http\Resources\ProductResource;

/**
 * Read model for the storefront home page.
 *
 * The page needs one catalogue snapshot; the API endpoints stay untouched so
 * the mobile apps keep their existing contracts.
 */
class HomeService
{
    private const PRODUCT_RELATIONS = ['colors', 'sizes', 'images', 'videos', 'category', 'brand', 'city'];

    /**
     * @return array<int, array<string, mixed>>
     */
    public function categories(): array
    {
        $categories = Category::query()
            ->withCount('products')
            ->orderByDesc('sort_order')
            ->get();

        return CategoryResource::collection($categories)->resolve(request());
    }

    /**
     * "Our featured products": premium placements first, then the newest.
     *
     * @return array<int, array<string, mixed>>
     */
    public function featuredProducts(int $limit = 12): array
    {
        $products = Product::query()
            ->publiclyAvailable()
            ->with(self::PRODUCT_RELATIONS)
            ->orderByRaw(Product::HOME_PRIORITY_SQL.' desc')
            ->latest('created_at')
            ->limit($limit)
            ->get();

        return ProductResource::collection($products)->resolve(request());
    }

    /**
     * Paid marketplace listings (Premium / VIP).
     *
     * @return array<int, array<string, mixed>>
     */
    public function premiumListings(int $limit = 10): array
    {
        $products = Product::query()
            ->publiclyAvailable()
            ->with(self::PRODUCT_RELATIONS)
            ->where(function ($query) {
                $query->where('is_premium', true)->orWhere('is_vip', true);
            })
            ->orderByRaw(Product::PLACEMENT_PRIORITY_SQL.' desc')
            ->latest('created_at')
            ->limit($limit)
            ->get();

        return ProductResource::collection($products)->resolve(request());
    }

    /**
     * The operator's single home-page popup, if one is active.
     *
     * @return array{type: string|null, image: string|null, video: string|null}|null
     */
    public function popup(): ?array
    {
        $popup = Popup::query()->where('show_on_home_page', true)->first();

        if (! $popup) {
            return null;
        }

        return [
            'type' => $popup->type,
            'image' => $popup->image,
            'video' => $popup->video,
        ];
    }
}
