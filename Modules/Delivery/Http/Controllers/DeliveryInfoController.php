<?php

namespace Modules\Delivery\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Interfaces\IBaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Delivery\Services\DeliveryInfoService;

class DeliveryInfoController extends Controller
{
    private DeliveryInfoService $service;

    public function __construct(DeliveryInfoService $service)
    {
        $this->service = $service;
    }

    public function getAll(Request $request): JsonResponse
    {
        return $this->service->getAll($request);
    }

    public function getByType($type): JsonResponse
    {
        return $this->service->getByType($type);
    }

    public function update($id, Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|string|in:STANDARD,STANDARD_FAST,PICKUP_POINT,TAKE_FROM_STORE',
            'description' => 'required|array',
            'description.en' => 'required|string',
            'description.tr' => 'nullable|string',
            'description.az' => 'nullable|string',
            'description.ru' => 'nullable|string',
        ]);

        return $this->service->update($id, $request);
    }
}
