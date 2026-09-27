<?php

namespace Modules\Live\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Modules\Product\Http\Entities\Product;

/**
 * Makes a product shown outside the catalogue look exactly like one inside it.
 *
 * The app parses products with one model everywhere. A product in a stream, in
 * a product.changed push or on the rebuilt home screen used to arrive with only
 * some of its relations loaded — 36 or 38 of the 42 fields /api/product returns
 * — so the card had no sizes or colours to pick and no rating to show. These
 * mirror what ProductService::list() loads and fills in afterwards.
 */
final class CatalogueProducts
{
    public const RELATIONS = ['colors', 'sizes', 'images', 'videos', 'category', 'brand', 'store'];

    /** Eager-loads a product query the way the catalogue list does. */
    public static function load(Builder|Relation $query): Builder|Relation
    {
        return $query->with(self::RELATIONS)->withAvg('reviews', 'rate')->withCount('reviews');
    }

    /**
     * Fills the fields the list computes after paginating. Two queries cover
     * the whole set, where the list asks twice per product.
     *
     * A broadcast goes to everyone, so there nothing may be personal: pass
     * $forViewer false and the favourite and subscription flags come out false.
     *
     * @param  iterable<Product|null>  $products
     */
    public static function decorate(iterable $products, bool $forViewer = true): void
    {
        $products = collect($products)->filter();

        if ($products->isEmpty()) {
            return;
        }

        $user = $forViewer ? auth('sanctum')->user() : null;
        $ids = $products->pluck('id')->all();

        $flagged = fn (string $relation) => $user
            ? Product::query()
                ->whereIn('id', $ids)
                ->whereHas($relation, fn ($query) => $query->where('user_id', $user->id))
                ->pluck('id')
                ->flip()
            : collect();

        $favourites = $flagged('favoritedBy');
        $subscriptions = $flagged('subscribedBy');

        foreach ($products as $product) {
            $product->rate = $product->reviews_avg_rate !== null ? round($product->reviews_avg_rate, 2) : 0;
            $product->rate_count = $product->reviews_count;
            $product->is_favorite = $favourites->has($product->id);
            $product->is_subscribe = $subscriptions->has($product->id);
        }
    }
}
