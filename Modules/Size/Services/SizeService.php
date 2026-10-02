<?php

namespace Modules\Size\Services;

use App\Support\TenantContext;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Modules\Size\Entities\Size;
use Modules\Size\Http\Transformers\SizeResource;

class SizeService
{
    private Size $model;

    public function __construct(Size $model)
    {
        $this->model = $model;
    }

    public function list($request): JsonResponse
    {
        $params = $request->all();
        $perPage = max(1, min((int) ($params['per_page'] ?? $params['limit'] ?? 20), 500));
        $query = $this->model->query()->select(['id', 'name', 'icon', 'is_active', 'sort_order']);
        $query = filterLike($query, ['name'], $params);

        if (isset($params['is_active'])) {
            $query->where('is_active', $params['is_active']);
        } else {
            $query->where('is_active', 1);
        }

        $data = $query
            ->orderByDesc('sort_order')
            ->orderByDesc('id')
            ->paginate($perPage);

        return responseHelper(__('Sizes retrieved successfully.'), 200, SizeResource::collection($data));

        //        return response()->json([
        //            'success' => 200,
        //            'message' => __('Sizes retrieved successfully.'),
        //            'data' => SizeResource::collection($data),
        //            'meta' => [
        //                'current_page' => $data->currentPage(),
        //                'last_page' => $data->lastPage(),
        //                'per_page' => $data->perPage(),
        //                'total' => $data->total(),
        //            ],
        //        ]);
    }

    /**
     * Size details
     */
    public function details(int $id): JsonResponse
    {
        try {
            $color = $this->model->findOrFail($id);

            return responseHelper(__('Size details retrieved successfully.'), 200, SizeResource::make($color));

            //            return response()->json([
            //                'success' => 200,
            //                'message' => __('Size details retrieved successfully.'),
            //                'data' => SizeResource::make($color),
            //            ]);

        } catch (ModelNotFoundException $e) {
            return responseHelper(__('Size not found.'), 200, []);
        }
    }

    /**
     * Add color
     */
    public function add($request): JsonResponse
    {
        $validated = $request->validated();

        if ($request->hasFile('icon')) {
            $icon = $request->file('icon');
            $iconName = time().'_'.$icon->getClientOriginalName();
            $directory = TenantContext::storagePath('sizes');

            if (! Storage::disk('public')->exists($directory)) {
                Storage::disk('public')->makeDirectory($directory, 0755, true);
            }

            $icon->storeAs($directory, $iconName, 'public');
            $validated['icon'] = $directory.'/'.$iconName;
        }
        $maxSortOrder = $this->model->max('sort_order') ?? 0;
        $validated['sort_order'] = $maxSortOrder + 1;

        $color = handleTransaction(
            fn () => $this->model->create($validated)->refresh(),
            'Size added successfully.',
            SizeResource::class
        );

        return $color;
    }

    /**
     * Update color
     */
    public function update($request, int $id): JsonResponse
    {
        $validated = $request->validated();

        if ($request->hasFile('icon')) {
            $icon = $request->file('icon');
            $iconName = time().'_'.$icon->getClientOriginalName();
            $directory = TenantContext::storagePath('sizes');

            if (! Storage::disk('public')->exists($directory)) {
                Storage::disk('public')->makeDirectory($directory, 0755, true);
            }

            $icon->storeAs($directory, $iconName, 'public');
            $validated['icon'] = $directory.'/'.$iconName;
        }
        $color = handleTransaction(
            function () use ($validated, $id) {
                $color = $this->model->findOrFail($id);
                $color->update($validated);

                return $color->refresh();
            },
            'Size updated successfully.',
            SizeResource::class
        );

        return $color;
    }

    /**
     * Delete color
     */
    public function delete(int $id): JsonResponse
    {
        $response = handleTransaction(
            function () use ($id) {
                $color = $this->model->findOrFail($id);
                $color->delete();

                return $color;
            },
            'Size deleted successfully.'
        );

        return $response;
    }
}
