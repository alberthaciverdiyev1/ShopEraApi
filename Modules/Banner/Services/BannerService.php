<?php

namespace Modules\Banner\Services;

use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Modules\Banner\Entities\Banner;
use Modules\Banner\Http\Resources\BannerResource;
use Modules\Product\Entities\Product;

class BannerService
{
    private const TYPES = ['big', 'middle', 'small'];

    private Banner $model;

    public function __construct(Banner $model)
    {
        $this->model = $model;
    }

    public function getAll($request): array|JsonResponse
    {
        $query = $this->model::query()
            ->with(['product.images', 'product.brand', 'product.category', 'product.colors', 'product.sizes'])
            ->where('is_active', true);

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }
        $banners = $request->has('page')
            ? $query->latest()->paginate(min(max((int) $request->input('per_page', 20), 1), 100))
            : $query->latest()->get();

        return responseHelper(__('Banners retrieved successfully'), 200, BannerResource::collection($banners));
    }

    public function add($request)
    {
        if (! $request->hasFile('image')) {
            return responseHelper(__('Image is required'), 422);
        }

        if (! $request->filled('type')) {
            return responseHelper(__('Type is required'), 422);
        }

        if (! in_array($request->input('type'), self::TYPES, true)) {
            return responseHelper(__('Type must be one of: big, middle, small'), 422);
        }

        if ($request->filled('product_id') && ! Product::query()->whereKey($request->input('product_id'))->exists()) {
            return responseHelper(__('Product not found'), 422);
        }

        return handleTransaction(function () use ($request) {

            $file = $request->file('image');
            $secondImage = $request->file('second_image');

            if (! $file->isValid()) {
                return responseHelper(__('Invalid image file'), 422);
            }

            $path = $file->store(TenantContext::storagePath('banner'), 'public');

            $secondPath = null;

            if ($secondImage) {
                if (! $secondImage->isValid()) {
                    return responseHelper(__('Invalid second image file'), 422);
                }

                $secondPath = $secondImage->store(TenantContext::storagePath('banner'), 'public');
            }

            $this->model->create([
                'image' => $path,
                'second_image' => $secondPath,
                'type' => $request->input('type'),
                'url' => $request->input('url'),
                'product_id' => $request->input('product_id'),
                'title' => $request->input('title'),
                'subtitle' => $request->input('subtitle'),
                'is_active' => $request->boolean('is_active', true),
            ]);

            return responseHelper(__('Banner added successfully'), 200);

        }, 'Error occurred while adding banner');
    }

    public function delete($id)
    {
        $banner = $this->model::find($id);
        if (! $banner) {
            return responseHelper(__('Banner not found'), 404);
        }

        return handleTransaction(function () use ($banner) {
            $banner->delete();

            return responseHelper(__('Banner deleted successfully'), 200);
        }, 'Error occurred while deleting banner');
    }

    /** Product select options: [id => label]. */
    public function productOptions(): array
    {
        $options = ['' => '— Məhsul seçilməyib —'];

        foreach (Product::query()->orderByDesc('id')->limit(500)->get() as $product) {
            $options[$product->id] = admin_label($product, 'title', '#'.$product->id);
        }

        return $options;
    }
}
