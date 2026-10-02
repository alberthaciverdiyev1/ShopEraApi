<?php

namespace Modules\Delivery\Services;

use Exception;
use Illuminate\Http\JsonResponse;
use Log;
use Modules\Delivery\Entities\DeliveryInfo;

class DeliveryInfoService
{
    private DeliveryInfo $model;

    public function __construct(DeliveryInfo $model)
    {
        $this->model = $model;
    }

    public function getAll($request): JsonResponse
    {
        $query = $this->model->query()->select(['id', 'type', 'description']);

        if ($request->has('type') && $request->type === 'delivery') {
            $query->whereIn('type', ['STANDARD', 'STANDARD_FAST']);
        }

        if ($request->has('type') && $request->type === 'pickup') {
            $query->whereIn('type', ['PICKUP_POINT', 'TAKE_FROM_STORE']);
        }

        $info = $query->orderBy('id')->get();

        return response()->json($info);
    }

    public function getByType(string $type): JsonResponse
    {
        $type = strtoupper($type);
        $info = $this->model->where('type', $type)->first();

        return response()->json($info);
    }

    public function update(int $id, $request): JsonResponse
    {
        try {
            $deliveryInfo = $this->model->findOrFail($id);

            $deliveryInfo->update([
                'type' => $request->type,
                'description' => $request->description,
            ]);

            return response()->json([
                'message' => 'Delivery info updated successfully',
                'data' => $deliveryInfo,
            ], 200);

        } catch (Exception $e) {
            Log::error('DeliveryInfo Update Error: '.$e->getMessage());

            return response()->json([
                'message' => 'Update failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
