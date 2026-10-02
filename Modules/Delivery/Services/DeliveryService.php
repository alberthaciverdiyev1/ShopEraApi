<?php

namespace Modules\Delivery\Services;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Modules\Delivery\Entities\City;
use Modules\Delivery\Entities\Delivery;
use Modules\Delivery\Http\Resources\DeliveryResource;

class DeliveryService
{
    private Delivery $model;

    public function __construct(Delivery $model)
    {
        $this->model = $model;
    }

    /**
     * list
     */
    public function list($request): JsonResponse
    {
        $params = $request->all();

        $query = $this->model->query()->select(['id', 'city_name', 'price', 'fast_price', 'is_active', 'free_from', 'delivery_time', 'fast_delivery_time']);

        if (! empty($params['search'])) {
            $search = mb_strtolower(trim($params['search']));
            $cityKeys = City::query()
                ->whereRaw('LOWER(key) LIKE ?', ["%{$search}%"])
                ->orWhereRaw('LOWER(name) LIKE ?', ["%{$search}%"])
                ->pluck('key');

            $query->whereIn('city_name', $cityKeys);
        }

        if (! isset($params['is_admin']) || ! $params['is_admin']) {
            $query->where('is_active', 1);
        }

        $data = $query->orderBy('price', 'desc')->paginate(20);

        return responseHelper(__('Deliveries retrieved successfully.'), 200, DeliveryResource::collection($data));
    }

    /**
     * details
     */
    public function detailsForMobile(?int $id, string $name): JsonResponse
    {
        try {
            if (! $id && ! $name) {

                return responseHelper(__('Either ID or city name must be provided.'), 400, []);

            }

            $delivery = $this->findActiveDelivery($id, $name);

            return responseHelper(__('Delivery details retrieved successfully.'), 200, DeliveryResource::make($delivery));

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => 403,
                'message' => __('Delivery not found.'),
                'data' => [],
            ]);
        }
    }

    public function details(?int $id = null, ?string $name = null): JsonResponse
    {
        try {
            if (! $id && ! $name) {
                return response()->json([
                    'success' => 400,
                    'message' => __('Either ID or city name must be provided.'),
                    'data' => [],
                ]);
            }

            $delivery = $this->findActiveDelivery($id, $name);

            return response()->json([
                'success' => 200,
                'message' => __('Delivery details retrieved successfully.'),
                'data' => DeliveryResource::make($delivery),
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => 403,
                'message' => __('Delivery not found.'),
                'data' => [],
            ]);
        }
    }

    public function cities()
    {
        $cities = City::query()
            ->active()
            ->orderBy('name')
            ->get(['key', 'name']);

        return responseHelper(__('Cities retrieved successfully.'), 200, $cities);
    }

    /**
     * Add
     */
    public function add($request): JsonResponse
    {
        $validated = $request->validated();

        try {
            $existing = $this->model->withTrashed()
                ->where('city_name', $validated['city_name'])
                ->first();

            if ($existing) {
                if ($existing->trashed()) {
                    handleTransaction(function () use ($existing, $validated) {
                        $existing->restore();
                        $existing->update($validated);
                    });

                    return response()->json([
                        'success' => 200,
                        'message' => __('Delivery entry was previously deleted, restored and updated with new values.'),
                        'data' => new DeliveryResource($existing->refresh()),
                    ]);
                }

                return response()->json([
                    'success' => 400,
                    'message' => __('A delivery entry for this city already exists.'),
                    'data' => [],
                ]);
            }

            return handleTransaction(
                fn () => $this->model->create($validated)->refresh(),
                'Delivery added successfully.',
                DeliveryResource::class
            );

        } catch (\Exception $e) {
            Log::error('Delivery error', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => 500,
                'message' => 'Technical error occurred.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update
     */
    public function update($request, int $id): JsonResponse
    {
        $validated = $request->validated();

        $delivery = handleTransaction(
            function () use ($validated, $id) {
                $delivery = $this->model->findOrFail($id);
                $delivery->update($validated);

                return $delivery->refresh();
            },
            'Delivery updated successfully.',
            DeliveryResource::class
        );

        return $delivery;
    }

    /**
     * DELETE
     */
    public function delete(int $id): JsonResponse
    {
        $response = handleTransaction(
            function () use ($id) {
                $delivery = $this->model->findOrFail($id);
                $delivery->delete();

                return $delivery;
            },
            'Delivery deleted successfully.'
        );

        return $response;
    }

    private function findActiveDelivery(?int $id, ?string $name): Delivery
    {
        if ($id) {
            $delivery = $this->model->newQuery()->where('is_active', true)->findOrFail($id);
            $city = City::findMatching($delivery->city_name);

            if (! $city) {
                throw new ModelNotFoundException;
            }

            return $delivery;
        }

        $city = City::findMatching($name);

        if (! $city) {
            throw new ModelNotFoundException;
        }

        return $this->model->newQuery()
            ->where('is_active', true)
            ->where('city_name', $city->key)
            ->firstOrFail();
    }
}
