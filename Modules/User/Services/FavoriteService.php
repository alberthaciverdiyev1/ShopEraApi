<?php

namespace Modules\User\Services;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Modules\Product\Http\Entities\Product;
use Modules\Product\Http\Resources\ProductResource;

class FavoriteService
{
    private Product $product;

    public function __construct(Product $product)
    {
        $this->product = $product;
    }

    /**
     * List all favorites for the authenticated user
     */
    public function list(): JsonResponse
    {
        $user = Auth::user();

        $favorites = $user->favorites()
            ->with(['brand', 'category', 'images','colors','sizes'])
            ->withAvg('reviews', 'rate')
            ->withCount('reviews')
            ->orderBy('user_favorites.created_at', 'desc')
            ->paginate(20);

        $favorites->getCollection()->transform(function ($product) {
            $product->is_favorite = true;
            $product->rate = ($product->reviews_avg_rate !== null) ? round($product->reviews_avg_rate, 2) : 0;
            $product->rate_count = $product->reviews_count;
            return $product;
        });

        return responseHelper(__('Favorites retrieved successfully.'), 200, ProductResource::collection($favorites->items()));
    }

    /**
     * Add product to favorites
     */
    public function add(int $productId): JsonResponse
    {
        $user = Auth::user();

        $product = $this->product->find($productId);
        if (!$product) {
            return responseHelper(__('Product not found.'), 403);
        }

        $result = $user->favorites()->toggle([$product->id]);

        $action = count($result['attached']) > 0 ? 'added' : 'removed';

        return responseHelper(($action === 'added' ? 'Product added to favorites.' : 'Product removed from favorites.'), 200);
    }

    /**
     * Remove product from favorites
     */
    public function delete(int $productId): JsonResponse
    {
        $user = Auth::user();

        $product = $this->product->find($productId);

        if (!$product) {
            return responseHelper(__('Product not found.'), 403);
        }

        if ($user->favorites()->where('product_id', $product->id)->exists()) {
            $user->favorites()->detach($product->id);
            return responseHelper(__('Product removed from favorites.'), 200);
        }
        return responseHelper(__('Product was not in favorites.'), 200);

    }

}
