<?php

namespace Modules\Store\Services;

use Illuminate\Support\Facades\DB;
use Modules\Product\Http\Entities\Product;
use Modules\Store\Http\Entities\Store;

/**
 * The customer-facing view of a store: what a shopper sees before buying.
 *
 * Rating is the weighted average the proposal asks for — the mean over every
 * review of every product in the store, not the mean of per-product means, so a
 * product with 100 reviews outweighs one with 2.
 */
class StoreProfileService
{
    public function summary(Store $store): array
    {
        $productIds = Product::publiclyAvailable()->where('store_id', $store->id)->pluck('id');

        $rating = DB::table('product_reviews')
            ->whereIn('product_id', $productIds)
            ->selectRaw('AVG(rate) as avg_rate, COUNT(*) as total')
            ->first();

        return [
            'id' => $store->id,
            'name' => $store->name,
            'about' => $store->about,
            'logo_path' => $store->logo_path,
            'is_trusted' => (bool) $store->is_trusted,
            'products_count' => $productIds->count(),
            'sales_count' => (int) Product::withTrashed()
                ->where('store_id', $store->id)->sum('sales_count'),
            'rating' => $rating?->avg_rate !== null ? round((float) $rating->avg_rate, 2) : null,
            'rating_count' => (int) ($rating?->total ?? 0),
            'created_at' => $store->created_at?->toIso8601String(),
        ];
    }

    /**
     * Reviews across all of the store's products, newest first, each carrying
     * the product it was written about.
     */
    public function reviews(Store $store, int $perPage)
    {
        $productIds = Product::publiclyAvailable()->where('store_id', $store->id)->pluck('id');

        return DB::table('product_reviews as r')
            ->join('users as u', 'u.id', '=', 'r.user_id')
            ->join('products as p', 'p.id', '=', 'r.product_id')
            ->whereIn('r.product_id', $productIds)
            ->orderByDesc('r.id')
            ->select([
                'r.id', 'r.rate', 'r.comment', 'r.created_at',
                'r.product_id', 'p.title as product_title',
                'u.name as user_name', 'u.surname as user_surname',
            ])
            ->paginate($perPage);
    }

    /** Only this store's catalogue, with the usual customer filters. */
    public function products(Store $store, array $params, int $perPage)
    {
        $query = Product::publiclyAvailable()
            ->with(['images', 'colors', 'sizes', 'category', 'brand', 'store'])
            ->withAvg('reviews', 'rate')
            ->withCount('reviews')
            ->where('store_id', $store->id);

        if (! empty($params['category_id'])) {
            $query->where('category_id', $params['category_id']);
        }

        if (! empty($params['brand_id'])) {
            $query->where('brand_id', $params['brand_id']);
        }

        if (! empty($params['color_id'])) {
            $query->whereHas('colors', fn ($q) => $q->where('colors.id', $params['color_id']));
        }

        if (! empty($params['size_id'])) {
            $query->whereHas('sizes', fn ($q) => $q->where('sizes.id', $params['size_id']));
        }

        if (! empty($params['search'])) {
            // title/description are translatable json columns; reuse the helper
            // the customer catalogue already searches with.
            filterLike($query, ['title', 'description', 'sku'], $params);
        }

        rangeFilter($query, 'price', $params);
        orderBy($query, $params);

        return $query->paginate($perPage);
    }
}
