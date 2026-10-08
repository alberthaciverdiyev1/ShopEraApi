<?php

namespace Modules\Product\Services;

use App\Enums\ReviewStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Modules\Product\Entities\Product;
use Modules\Product\Entities\Review;
use Modules\Product\Http\Resources\ReviewListResource;
use Modules\Product\Http\Resources\ReviewResource;

class ReviewService
{
    private Review $model;

    public function __construct(Review $model)
    {
        $this->model = $model;
    }

    /**
     * Review list
     */
    public function list(int $product_id): JsonResponse
    {
        $viewKey = 'product_view_'.$product_id.'_'.request()->ip();

        if (! Cache::has($viewKey)) {
            Product::where('id', $product_id)->increment('views');
            Cache::put($viewKey, true, now()->addMinutes(30));
        }

        $data = $this->model->with([
            'user',
            'product.category',
            'product.brand',
            'product.images',
        ])
            ->where('product_id', $product_id)
            ->where('status', ReviewStatus::APPROVED->value)
            ->orderBy('id', 'desc')
            ->paginate(20);

        return responseHelper(__('Reviews retrieved successfully.'), 200, ReviewResource::collection($data));
    }

    public function listAdmin(Request $request): JsonResponse
    {
        $query = $this->model->query()->with([
            'user',
            'product.category',
            'product.brand',
            'product.images',
        ]);

        if ($request->filled('status')) {
            $statusValue = (int) $request->status;

            $statusEnum = ReviewStatus::tryFrom($statusValue);

            if ($statusEnum) {
                $query->where('status', $statusEnum->value);
            }
        }

        $data = $query->orderBy('created_at', 'desc')->paginate(20);

        return responseHelper(__('Reviews retrieved successfully.'), 200, ReviewListResource::collection($data));
    }

    /**
     * Add review
     */
    /**
     * Reviews the admin picked for the "What our client say" home strip.
     */
    public function featured(): JsonResponse
    {
        $reviews = $this->model->query()
            ->with(['user', 'product.images'])
            ->where('status', ReviewStatus::APPROVED->value)
            ->where('is_featured', true)
            ->orderByDesc('id')
            ->limit(12)
            ->get()
            ->map(fn ($review) => [
                'id' => $review->id,
                'rate' => (int) $review->rate,
                'comment' => $review->comment,
                'created_at' => $review->created_at?->format('d.m.Y'),
                'user' => [
                    'id' => $review->user?->id,
                    'name' => $review->user?->name,
                    'avatar' => $review->user?->avatar,
                ],
                'product' => [
                    'id' => $review->product?->id,
                    'title' => $review->product?->title,
                    'image' => $review->product?->images->first()?->image_path,
                ],
            ]);

        return responseHelper(__('Featured reviews retrieved successfully.'), 200, $reviews);
    }

    public function add($request): JsonResponse
    {
        $validated = $request->validated();

        if (! empty($validated['image'])) {
            $url = compressAndUploadImage($validated['image'], 'reviews', 'review');
            $validated['image'] = ltrim(str_replace(url('/'), '', $url), '/');
        }

        $validated['status'] = ReviewStatus::PENDING->value;

        $response = handleTransaction(
            fn () => $this->model->create($validated)->refresh(),
            'Review added successfully.',
            ReviewResource::class
        );

        $this->recalcVendor((int) $validated['product_id']);

        return $response;
    }

    public function changeStatus(int $review_id, string $status): JsonResponse
    {
        $statusEnum = constant(ReviewStatus::class.'::'.strtoupper($status));

        return handleTransaction(function () use ($review_id, $statusEnum) {
            $this->model->where('id', $review_id)->update([
                'status' => $statusEnum->value,
            ]);
        }, 'Review status updated successfully.');
    }

    /**
     * Delete review
     */
    public function delete(int $id): JsonResponse
    {
        return handleTransaction(
            function () use ($id) {
                $review = $this->model->findOrFail($id);
                if ($review->user_id !== auth()->id()) {
                    abort(403, 'You are not allowed to delete this review.');
                }

                $review->delete();
                $this->recalcVendor((int) $review->product_id);

                return $review;
            },
            'Review deleted successfully.'
        );
    }

    public function deleteByAdmin(int $id): JsonResponse
    {
        return handleTransaction(
            function () use ($id) {
                $review = $this->model->findOrFail($id);

                $review->delete();
                $this->recalcVendor((int) $review->product_id);

                return $review;
            },
            'Review deleted successfully.'
        );
    }

    /** Admin listing query with status + text filters. */
    public function adminQuery(\Illuminate\Http\Request $request): \Illuminate\Database\Eloquent\Builder
    {
        $query = $this->model->newQuery()->with(['user', 'product.images'])->latest('id');

        if ($request->filled('status') && is_numeric($request->query('status'))) {
            $query->where('status', (int) $request->query('status'));
        }

        if (($term = trim((string) $request->query('q', ''))) !== '') {
            $query->where(function ($inner) use ($term) {
                $inner->where('comment', 'like', "%{$term}%")
                    ->orWhereHas('product', fn ($p) => $p->where('title->az', 'like', "%{$term}%"));
            });
        }

        return $query;
    }

    public function setStatus(int $id, int $status): void
    {
        $this->model->newQuery()->findOrFail($id)->update(['status' => $status]);
    }

    /** Toggles the home-page feature flag; only approved reviews can be featured. */
    public function toggleFeatured(int $id): bool
    {
        $review = $this->model->newQuery()->findOrFail($id);
        $featured = ! $review->is_featured;

        $review->update([
            'is_featured' => $featured,
            'status' => $featured ? \App\Enums\ReviewStatus::APPROVED->value : $review->status->value,
        ]);

        return $featured;
    }

    public function remove(int $id): void
    {
        $review = $this->model->newQuery()->findOrFail($id);
        $productId = (int) $review->product_id;
        $review->delete();
        $this->recalcVendor($productId);
    }

    /** Keep the product's store rating in sync after any review change. */
    private function recalcVendor(int $productId): void
    {
        $product = \Modules\Product\Entities\Product::query()->find($productId);

        if ($product?->vendor_id) {
            app(\Modules\Marketplace\Services\VendorService::class)
                ->recalcRating(\Modules\Marketplace\Entities\Vendor::find($product->vendor_id));
        }
    }
}
