<?php

namespace Modules\Brand\Services;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use App\Support\TenantContext;
use Modules\Brand\Entities\Brand;
use Modules\Brand\Http\Transformers\BrandDetailsResource;
use Modules\Brand\Http\Transformers\BrandResource;
use Modules\Product\Entities\Product;

class BrandService
{
    private Brand $model;

    public function __construct(Brand $model)
    {
        $this->model = $model;
    }

    public function list($request): JsonResponse
    {
        $params = $request->all();
        $cacheKey = 'brands_list_'.md5(serialize($params));

        $query = $this->model->query()->select(['id', 'name', 'image', 'is_active', 'sort_order']);
        $query = filterLike($query, ['name'], $params);

        // When a category is given, list only the brands linked to it (falling
        // back to every brand when that category has no links yet).
        if (! empty($params['category_id'])) {
            $categoryId = (int) $params['category_id'];
            $hasLinked = \Illuminate\Support\Facades\DB::table('brand_category')
                ->where('category_id', $categoryId)->exists();

            if ($hasLinked) {
                $query->whereHas('categories', fn ($q) => $q->where('categories.id', $categoryId));
            }
        }

        if (isset($params['is_active'])) {
            $query->where('is_active', $params['is_active']);
        } else {
            $query->where('is_active', 1);
        }

        $data = $query->orderBy('id', 'desc')->paginate(20);

        return responseHelper(__('Brands retrieved successfully.'), 200, BrandResource::collection($data));

    }

    /**
     * Brand details
     */
    public function details(int $id): JsonResponse
    {
        try {
            $brand = $this->model->with('products', 'products.images', 'products.colors', 'products.sizes', 'products.category')->findOrFail($id);

            return responseHelper(__('Brand details retrieved successfully.'), 200, BrandDetailsResource::make($brand));

        } catch (ModelNotFoundException $e) {
            return responseHelper(__('Brand not found.'), 403, []);
        }
    }

    /**
     * Add brand
     */
    public function add($request): JsonResponse
    {
        $validated = $request->validated();

        if ($request->hasFile('image')) {
            $image = $request->file('image');

            $originalName = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
            $extension = $image->getClientOriginalExtension();

            $cleanName = preg_replace('/\s+/', '', $originalName);

            $imageName = time().'_'.$cleanName.'.'.$extension;

            $directory = TenantContext::storagePath('brands');

            if (! Storage::disk('public')->exists($directory)) {
                Storage::disk('public')->makeDirectory($directory, 0755, true);
            }

            $image->storeAs($directory, $imageName, 'public');
            $validated['image'] = "{$directory}/{$imageName}";
        }

        $brand = handleTransaction(
            fn () => $this->model->create($validated)->refresh(),
            'Brand added successfully.',
            BrandResource::class
        );

        Cache::forget('brands_list_*');

        return $brand;
    }

    /**
     * Update brand
     */
    public function update($request, int $id): JsonResponse
    {
        $validated = $request->validated();

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time().'_'.$image->getClientOriginalName();

            $directory = TenantContext::storagePath('brands');

            if (! Storage::disk('public')->exists($directory)) {
                Storage::disk('public')->makeDirectory($directory, 0755, true);
            }

            $image->storeAs($directory, $imageName, 'public');
            $validated['image'] = "{$directory}/{$imageName}";
        }

        $brand = handleTransaction(
            function () use ($validated, $id) {
                $brand = $this->model->findOrFail($id);
                $brand->update($validated);

                return $brand->refresh();
            },
            'Brand updated successfully.',
            BrandResource::class
        );

        Cache::forget('brands_list_*');

        return $brand;
    }

    /**
     * Delete brand
     */
    public function delete(int $id): JsonResponse
    {
        $response = handleTransaction(
            function () use ($id) {
                $brand = $this->model->findOrFail($id);
                Product::where('brand_id', $brand->id)->update(['brand_id' => null]);
                $brand->delete();

                return $brand;
            },
            'Brand deleted successfully.'
        );

        Cache::forget('brands_list_*');

        return $response;
    }
}
