<?php

namespace Modules\Delivery\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Interfaces\IBaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Delivery\Http\Requests\PickupPointAddRequest;
use Modules\Delivery\Http\Requests\PickupPointUpdateRequest;
use Modules\Delivery\Services\PickupPointService;

class PickupPointController extends Controller implements IBaseController

{
    private PickupPointService $service;

    function __construct(PickupPointService $service)
    {
        $this->service = $service;
    }

    public function getAll(Request $request): JsonResponse
    {
        return $this->service->getAll($request);
    }

    public function details(int $id): JsonResponse
    {
        return $this->service->details($id);
    }

    public function detailsAdmin(Request $request,int $id): JsonResponse
    {
        return $this->service->detailsAdmin($request,$id);
    }

    public function add(Request $request): JsonResponse
    {
        $validatedData = app(PickupPointAddRequest::class)->validated();
        return $this->service->add($validatedData);
    }

    public function update(int $id, Request $request): JsonResponse
    {
        $validatedData = app(PickupPointUpdateRequest::class)->validated();

        return $this->service->update($id, $validatedData);
    }

    public function delete(int $id): JsonResponse
    {
        return $this->service->delete($id);
    }
}
