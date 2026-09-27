<?php

namespace Modules\User\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Request;
use Modules\User\Http\Requests\Basket\BasketAddRequest;
use Modules\User\Http\Requests\Basket\BasketUpdateRequest;
use Modules\User\Services\BasketService;

class BasketController
{
    private BasketService $service;

    public function __construct(BasketService $service)
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

    public function add(BasketAddRequest $request): JsonResponse
    {
        return $this->service->add($request);
    }

    public function update(int $id, BasketUpdateRequest $request): JsonResponse
    {
        return $this->service->update($id, $request);
    }

    public function delete(int $id): JsonResponse
    {
        return $this->service->delete($id);
    }
}
