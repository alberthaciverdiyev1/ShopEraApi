<?php

namespace Modules\Order\Http\Controllers;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Order\Http\Requests\OrderAddRequest;
use Modules\Order\Http\Requests\OrderBuyOneAddRequest;
use Modules\Order\Http\Requests\OrderUpdateRequest;
use Modules\Order\Http\Requests\PreviewOrderRequest;
use Modules\Order\Services\OrderService;
use Modules\User\Http\Requests\Address\AddressAddRequest;
use Modules\User\Http\Requests\Address\AddressUpdateRequest;
use Nwidart\Modules\Facades\Module;

class OrderController extends Controller
{

    private OrderService $service;

    public function __construct(OrderService $service)
    {
        $this->middleware('permission:view orders')->only('getAll');
        $this->middleware('permission:view orders-admin')->only('getAllAdmin');
        $this->middleware('permission:view orders')->only('details');
        $this->middleware('permission:basket order')->only('orderFromBasket');
        $this->middleware('permission:buy-one order')->only('buyOne');
        $this->middleware('permission:update order')->only('update');
        $this->middleware('permission:update order')->only('retryStarexShipment');
        $this->middleware('permission:delete order')->only('delete');
        $this->middleware('permission:completed-orders')->only('completedOrders');
        $this->middleware('permission:download-receipt')->only('downloadReceipt');
        $this->middleware('permission:view-receipt')->only('getReceipt');

        $this->service = $service;
    }

    public function getAll(Request $request): JsonResponse
    {
        return $this->service->getAll($request);
    }

    public function getAllAdmin(Request $request): JsonResponse
    {
        return $this->service->getAllAdmin($request);
    }

    public function detailsAdmin(string $id): JsonResponse
    {
        return $this->service->detailsAdmin($id);
    }

    public function details(int $id): JsonResponse
    {
        return $this->service->details($id);
    }

    public function orderFromBasket(OrderAddRequest $request)
    {
        return $this->service->orderFromBasket($request);
    }

    public function previewOrder(PreviewOrderRequest $request)
    {
        return $this->service->previewOrder($request);
    }

    public function buyOne(OrderBuyOneAddRequest $request, $product_id): JsonResponse
    {
        return $this->service->buyOne($request, $product_id);
    }

    public function update(int $id, OrderUpdateRequest $request): JsonResponse
    {
        return $this->service->update($id, $request);
    }

    public function retryStarexShipment(int $id): JsonResponse
    {
        return $this->service->retryStarexShipment($id);
    }

    public function delete(int $id): JsonResponse
    {
        return $this->service->delete($id);
    }

    public function getReceipt(int $orderId): JsonResponse
    {
        return $this->service->getReceipt($orderId);
    }

    public function downloadReceipt(int $orderId)
    {
        return $this->service->downloadReceipt($orderId);
    }

    public function completedOrders(Request $request)
    {
        return $this->service->completedOrders($request);
    }


    public function calculateDeliveryPrice(Request $request)
    {
        $addressType = $request->input('addressType');
        $addressTypeId = $request->input('addressTypeId', null);
        return $this->service->calculateDeliveryPrice($addressType, $addressTypeId);
    }
}
