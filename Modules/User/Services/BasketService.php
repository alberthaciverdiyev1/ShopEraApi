<?php

namespace Modules\User\Services;

use App\Interfaces\ICrudInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Modules\Product\Entities\Product;
use Modules\Product\Services\ProductPricingService;
use Modules\User\Entities\Basket;
use Modules\User\Http\Resources\BasketResource;

class BasketService implements ICrudInterface
{
    private Basket $model;

    private ProductPricingService $pricingService;

    public function __construct(Basket $model, ProductPricingService $pricingService)
    {
        $this->model = $model;
        $this->pricingService = $pricingService;
    }

    public function getAll($request): JsonResponse
    {
        $userId = auth()->id();

        $basketItems = $this->model
            ->with([
                'product' => function ($query) {
                    $query->with([
                        'brand',
                        'category',
                        'images',
                        'colors',
                        'sizes',
                        'reviews' => function ($q) {
                            $q->select('id', 'product_id', 'user_id', 'rate', 'comment', 'created_at');
                        },
                    ])
                        ->withAvg('reviews', 'rate')
                        ->withCount('reviews');
                },
            ])
            ->where('user_id', $userId)
            ->where('is_ordered', false)
            ->latest()
            ->get()
            ->map(function ($basketItem) {
                if ($basketItem->product) {
                    $product = clone $basketItem->product;
                    $prices = $this->pricingService->prices($product, $basketItem->size_id);
                    $product->setAttribute('resolved_price', $prices['original_price']);
                    $product->setAttribute('resolved_discount', $prices['discounted_price']);

                    $product->rate = $product->reviews_avg_rate !== null ? round($product->reviews_avg_rate, 2) : 0;
                    $product->rate_count = $product->reviews_count;
                    $product->is_favorite = Auth::check() && $product->favoritedBy()->where('user_id', Auth::id())->exists();

                    $basketItem->setRelation('product', $product);
                }

                return $basketItem;
            });
        //            ->get()
        //            ->map(function ($basketItem) {
        //                $product = $basketItem->product;
        //                if ($product) {
        //                    $selectedSizeId = $basketItem->size_id;
        //
        //                    $selectedSize = $product->sizes->where('id', $selectedSizeId)->first();
        //
        //                    if ($selectedSize && $selectedSize->pivot) {
        //                        $product->price = (float)$selectedSize->pivot->price;
        //                        $product->discount = (float)$selectedSize->pivot->discount;
        //                    }
        //
        //                    $product->rate = $product->reviews_avg_rate !== null ? round($product->reviews_avg_rate, 2) : 0;
        //                    $product->rate_count = $product->reviews_count;
        //
        //                    $product->is_favorite = Auth::check() && $product->favoritedBy()->where('user_id', Auth::id())->exists();
        //                }
        //                return $basketItem;
        //            });

        return responseHelper(__('Basket retrieved successfully.'),
            200,
            BasketResource::collection($basketItems)
        );
    }

    public function details(int $id): JsonResponse
    {
        return response()->json([]);
    }

    /**
     * Add address
     */
    public function add($request): JsonResponse
    {
        $validated = $request->validated();
        $validated['user_id'] = auth()->id();

        $product = Product::publiclyAvailable()->find($validated['product_id']);

        if (! $product) {
            return responseHelper(__('Product not found.'), 403);
        }

        if ($validated['quantity'] > $product->stock_count) {
            return responseHelper(
                "Only {$product->stock_count} units are available in stock.",
                403
            );
        }

        //        if ($this->hasPurchaseLimit($product) && $validated['quantity'] > (int) $product->purchase_limit) {
        //            return responseHelper(
        //                __("The maximum purchase limit for this product is :limit units.", [
        //                    'limit' => $product->purchase_limit
        //                ]),
        //                403
        //            );
        //        }

        $currentQuantity = $this->model
            ->where('product_id', $product->id)
            ->where('user_id', auth()->id())
            ->sum('quantity');

        $limit = $product->purchase_limit;

        if ($limit !== null && $limit > 0) {
            $remainingLimit = $limit - $currentQuantity;

            if ($validated['quantity'] > $remainingLimit) {
                return responseHelper(
                    //                    __("The maximum purchase limit for this product is :limit units. You can add :remaining more.", [
                    //                        'limit' => $limit,
                    //                        'remaining' => $remainingLimit
                    //                    ]),
                    __('The maximum purchase limit for this product is :limit units.', [
                        'limit' => $product->purchase_limit,
                    ]),
                    403
                );
            }
        }

        if (! $product->colors()->exists()) {
            $validated['color_id'] = null;
        }

        if (! $product->sizes()->exists()) {
            $validated['size_id'] = null;
        }

        if (isset($validated['color_id']) && $product->colors()->exists()) {
            if (! $product->colors()->where('color_id', $validated['color_id'])->exists()) {
                return responseHelper(__('Selected color is not available for this product.'), 403);
            }
        }

        if (isset($validated['size_id']) && $product->sizes()->exists()) {
            if (! $product->sizes()->where('size_id', $validated['size_id'])->exists()) {
                return responseHelper(__('Selected size is not available for this product.'), 403);
            }
        }

        return handleTransaction(
            fn () => $this->model->create($validated)->refresh(),
            'Basket added successfully.',
            BasketResource::class
        );
    }

    public function update(int $id, $request): JsonResponse
    {
        try {
            $data = $request->validated();

            $basket = $this->model
                ->with('product')
                ->where('user_id', auth()->id())
                ->findOrFail($id);

            $product = $basket->product;

            if (isset($data['quantity'])) {
                if ($data['quantity'] > $product->stock_count) {
                    return responseHelper(
                        "Only {$product->stock_count} units are available in stock.",
                        403
                    );
                }

                if ($this->hasPurchaseLimit($product) && $data['quantity'] > (int) $product->purchase_limit) {
                    return responseHelper(
                        "The maximum purchase limit for this product is {$product->purchase_limit} units.",
                        403
                    );
                }
            }

            if (! isset($data['color_id'])) {
                unset($data['color_id']);
            }
            if (! isset($data['size_id'])) {
                unset($data['size_id']);
            }

            return handleTransaction(
                fn () => tap($basket)->update($data)->refresh(),
                'Basket updated successfully.',
                BasketResource::class
            );

        } catch (ModelNotFoundException $e) {
            return responseHelper(__('Basket not found.'), 403, []);
        }
    }

    public function delete(int $id): JsonResponse
    {
        try {
            $basket = $this->model
                ->where('user_id', auth()->id())
                ->findOrFail($id);

            return handleTransaction(
                fn () => tap($basket)->delete(),
                'Basket deleted successfully.'
            );
        } catch (ModelNotFoundException $e) {
            return responseHelper(__('Basket not found.'), 403, []);
        }
    }

    private function hasPurchaseLimit(Product $product): bool
    {
        return $product->purchase_limit !== null && (int) $product->purchase_limit > 0;
    }
}
