<?php

namespace Modules\Product\Services;

use App\Enums\ReviewStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Modules\Product\Http\Entities\Product;
use Modules\Product\Http\Entities\Review;
use Modules\Product\Http\Resources\ReviewListResource;
use Modules\Product\Http\Resources\ReviewResource;

class ReviewService
{
    private Review $model;

    /**
     * @param Review $model
     */
    public function __construct(Review $model)
    {
        $this->model = $model;
    }

    /**
     * Review list
     */
    public function list(int $product_id): JsonResponse
    {
        $viewKey = 'product_view_' . $product_id . '_' . request()->ip();

        if (!Cache::has($viewKey)) {
            Product::where('id', $product_id)->increment('views');
            Cache::put($viewKey, true, now()->addMinutes(30));
        }

        $data = $this->model->with([
            'user',
            'product.category',
            'product.brand',
            'product.images'
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
            'product.images'
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
    public function add($request): JsonResponse
    {
        $validated = $request->validated();

        if (!empty($validated['image'])) {
            $url = compressAndUploadImage($validated['image'], 'reviews', 'review');
            $validated['image'] = ltrim(str_replace(url('/'), '', $url), '/');
        }

        $validated['status'] = ReviewStatus::PENDING->value;

        return handleTransaction(
            fn() => $this->model->create($validated)->refresh(),
            'Review added successfully.',
            ReviewResource::class
        );
    }

    public function changeStatus(int $review_id, string $status): JsonResponse
    {
        $statusEnum = constant(ReviewStatus::class . '::' . strtoupper($status));

        return handleTransaction(function () use ($review_id, $statusEnum) {
            $this->model->where('id', $review_id)->update([
                'status' => $statusEnum->value
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
                return $review;
            },
            'Review deleted successfully.'
        );
    }
}
