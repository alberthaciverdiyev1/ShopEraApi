<?php

namespace Modules\Order\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Order\Http\Requests\OrderAddRequest;
use Modules\Order\Http\Requests\OrderBuyOneAddRequest;
use Modules\Order\Http\Requests\OrderUpdateRequest;
use Modules\Order\Http\Requests\PreviewOrderRequest;
use Modules\Order\Services\OrderService;
use Modules\Setting\Entities\Setting;
use Modules\User\Entities\Basket;

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
        $this->middleware('permission:delete order')->only('delete');
        $this->middleware('permission:completed-orders')->only('completedOrders');
        $this->middleware('permission:download-receipt')->only('downloadReceipt');
        $this->middleware('permission:view-receipt')->only('getReceipt');

        $this->service = $service;
    }

    /**
     * Builds a WhatsApp deep link with the current basket, so small sellers
     * without a card account can take the order in chat.
     */
    public function whatsappLink(Request $request): JsonResponse
    {
        $user = $request->user();

        $items = Basket::query()->with('product')->where('user_id', $user->id)->get();

        if ($items->isEmpty()) {
            return responseHelper(__('Basket is empty.'), 422);
        }

        $lines = [];
        $total = 0.0;
        $index = 1;

        foreach ($items as $item) {
            $product = $item->product;
            $quantity = (int) $item->quantity;

            $unit = $product?->price ?? 0;
            if ($product?->discount !== null && (float) $product->discount > 0 && (float) $product->discount < (float) $unit) {
                $unit = $product->discount;
            }

            $lineTotal = (float) $unit * $quantity;
            $total += $lineTotal;

            $name = is_array($product?->title) ? ($product->title['az'] ?? reset($product->title)) : ($product?->title ?? 'Məhsul');
            $lines[] = $index++.') '.$name.' x'.$quantity.' = '.number_format($lineTotal, 2).' ₼';
        }

        $number = preg_replace('/\D/', '', (string) (Setting::query()->value('whatsapp_number') ?? ''));

        if (strlen($number) === 10 && str_starts_with($number, '0')) {
            $number = '994'.substr($number, 1);
        } elseif (strlen($number) === 9) {
            $number = '994'.$number;
        }

        $text = "Salam! Sifariş etmək istəyirəm:\n".implode("\n", $lines)
            ."\n\nCəm: ".number_format($total, 2).' ₼'
            ."\nAd: ".trim(($user->name ?? '').' '.($user->surname ?? ''));

        $url = $number !== ''
            ? 'https://wa.me/'.$number.'?text='.rawurlencode($text)
            : null;

        return responseHelper(__('WhatsApp link ready.'), 200, [
            'number' => $number,
            'url' => $url,
            'total' => round($total, 2),
        ]);
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
