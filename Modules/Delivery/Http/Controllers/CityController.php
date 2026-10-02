<?php

namespace Modules\Delivery\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Interfaces\IBaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Delivery\Services\CityService;

class CityController extends Controller implements IBaseController
{
    private CityService $service;

    public function __construct(CityService $service)
    {
        $this->service = $service;
    }

    public function getAll(Request $request): JsonResponse
    {
        return $this->service->getAll($request);
    }

    public function details($id): JsonResponse
    {
        return $this->service->details($id);
    }

    public function add(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|min:2',
        ]);

        return $this->service->add($request);
    }

    public function update($id, Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|min:2',
        ]);

        return $this->service->update($id, $request);
    }

    public function delete($id): JsonResponse
    {
        return $this->service->delete($id);
    }
}
