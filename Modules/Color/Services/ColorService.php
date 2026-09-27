<?php

namespace Modules\Color\Services;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Modules\Color\Http\Entities\Color;
use Modules\Color\Http\Transformers\ColorResource;

class ColorService
{
    private Color $model;

    /**
     * @param Color $model
     */
    function __construct(Color $model)
    {
        $this->model = $model;
    }

    /**
     * @param $request
     * @return JsonResponse
     */
    public function list($request): JsonResponse
    {
        $params = $request->all();
        $perPage = max(1, min((int)($params['per_page'] ?? $params['limit'] ?? 20), 500));
        $query = $this->model->query()->select(['id', 'name', 'hex', 'is_active', 'sort_order']);
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

        return responseHelper(__('Colors retrieved successfully.'), 200, ColorResource::collection($data));

//        return response()->json([
//            'success' => 200,
//            'message' => __('Colors retrieved successfully.'),
//            'data' => ColorResource::collection($data),
//            'meta' => [
//                'current_page' => $data->currentPage(),
//                'last_page' => $data->lastPage(),
//                'per_page' => $data->perPage(),
//                'total' => $data->total(),
//            ],
//        ]);
    }

    /**
     * Color details
     */
    public function details(int $id): JsonResponse
    {
        try {
            $color = $this->model->findOrFail($id);

            return responseHelper(__('Colors retrieved successfully.'), 200, ColorResource::make($color));

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return responseHelper(__('Colors not found.'), 403, []);
        }
    }

    /**
     * Add color
     */
    public function add($request): JsonResponse
    {
        $validated = $request->validated();

        $maxSortOrder = $this->model->max('sort_order') ?? 0;
        $validated['sort_order'] = $maxSortOrder + 1;

        $color = handleTransaction(
            fn() => $this->model->create($validated)->refresh(),
            'Color added successfully.',
            ColorResource::class
        );

        return $color;
    }


    /**
     * Update color
     */
    public function update($request, int $id): JsonResponse
    {
        $validated = $request->validated();

        $color = handleTransaction(
            function () use ($validated, $id) {
                $color = $this->model->findOrFail($id);
                $color->update($validated);
                return $color->refresh();
            },
            'Color updated successfully.',
            ColorResource::class
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
                $color->products()->detach();
                $color->delete();
                return $color;
            },
            'Color deleted successfully.'
        );

        return $response;
    }
}
