<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\PlaceOrderRequest;
use App\Http\Requests\Storefront\StoreAddressRequest;
use App\Support\Concerns\InteractsWithModuleControllers;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Delivery\Http\Controllers\DeliveryController;
use Modules\Delivery\Http\Controllers\PickupPointController;
use Modules\Order\Http\Controllers\OrderController;
use Modules\Order\Http\Requests\OrderAddRequest;
use Modules\Order\Http\Requests\PreviewOrderRequest;
use Modules\User\Http\Controllers\AddressController;
use Modules\User\Http\Controllers\BasketController;
use Modules\User\Http\Requests\Address\AddressAddRequest;

/**
 * Checkout page. Not linked from the navigation: the marketplace flow is
 * listing-based, so it stays reachable only by its own URL.
 */
class CheckoutController extends Controller
{
    use InteractsWithModuleControllers;

    public function index(Request $request): View
    {
        $user = $request->user();
        $items = (array) $this->payload(
            app(BasketController::class)->getAll($this->moduleHttpRequest())
        );
        $addresses = (array) $this->payload(
            app(AddressController::class)->getAll($this->moduleHttpRequest())
        );

        $deliveryType = (string) $request->query('delivery', 'STANDARD');
        $deliveryType = in_array($deliveryType, ['STANDARD', 'STANDARD_FAST', 'PICKUP_POINT', 'TAKE_FROM_STORE'], true)
            ? $deliveryType
            : 'STANDARD';

        $addressId = (int) $request->query('address', 0) ?: null;
        if (! $addressId && $addresses) {
            foreach ($addresses as $address) {
                if (! empty($address['is_default'])) {
                    $addressId = (int) $address['id'];
                    break;
                }
            }
            $addressId ??= (int) $addresses[0]['id'];
        }

        $pickupPointId = (int) $request->query('pickup', 0) ?: null;
        $promoCode = trim((string) $request->query('promo', ''));

        $preview = null;
        $previewError = null;

        if ($user && $items) {
            $addressTypeId = match ($deliveryType) {
                'PICKUP_POINT' => $pickupPointId,
                'TAKE_FROM_STORE' => null,
                default => $addressId,
            };

            if (($deliveryType !== 'STANDARD' && $deliveryType !== 'STANDARD_FAST') || $addressId) {
                $form = $this->moduleRequest(PreviewOrderRequest::class, array_filter([
                    'address_type' => $deliveryType,
                    'address_type_id' => $addressTypeId,
                    'promo_code' => $promoCode ?: null,
                ], fn ($value) => $value !== null), 'GET');

                $result = $this->envelope(
                    app(OrderController::class)->previewOrder($form)->getData(true)
                );

                if ($result['success']) {
                    $preview = $result['data'];
                } else {
                    $previewError = $result['message'];
                }
            }
        }

        return view('storefront.checkout.index', [
            'items' => $items,
            'total' => $this->total($items),
            'addresses' => $addresses,
            'cities' => (array) $this->payload(app(DeliveryController::class)->cities()),
            'pickupPoints' => (array) $this->payload(
                app(PickupPointController::class)->getAll(
                    $this->moduleHttpRequest(['is_active' => 1])
                )
            ),
            'deliveryType' => $deliveryType,
            'addressId' => $addressId,
            'pickupPointId' => $pickupPointId,
            'promoCode' => $promoCode,
            'preview' => $preview,
            'previewError' => $previewError,
        ]);
    }

    public function storeAddress(StoreAddressRequest $request): RedirectResponse
    {
        $form = $this->moduleRequest(AddressAddRequest::class, $request->validated(), 'POST');

        $payload = app(AddressController::class)->add($form)->getData(true);

        if (! ($payload['success'] ?? false)) {
            return redirect()
                ->route('storefront.checkout')
                ->withInput()
                ->with('error', $payload['message'] ?? 'Ünvan əlavə edilə bilmədi.');
        }

        $createdId = $payload['data']['id'] ?? null;

        return redirect()
            ->route('storefront.checkout', array_filter(['address' => $createdId]))
            ->with('status', 'Ünvan əlavə edildi.');
    }

    public function place(PlaceOrderRequest $request): RedirectResponse
    {
        $form = $this->moduleRequest(OrderAddRequest::class, $request->validated(), 'POST');

        $result = $this->envelope(
            app(OrderController::class)->orderFromBasket($form)->getData(true)
        );

        if (! $result['success']) {
            return redirect()
                ->route('storefront.checkout')
                ->withInput()
                ->with('error', $result['message'] ?: 'Sifariş yaradıla bilmədi.');
        }

        $paymentUrl = $result['data']['payment_url'] ?? null;

        if ($paymentUrl) {
            return redirect()->away($paymentUrl);
        }

        return redirect()
            ->route('storefront.orders')
            ->with('status', 'Sifarişiniz qəbul edildi.');
    }

    public function whatsapp(): RedirectResponse
    {
        $url = $this->payload(
            app(OrderController::class)->whatsappLink($this->moduleHttpRequest())
        )['url'] ?? null;

        if (! $url) {
            return redirect()
                ->route('storefront.checkout')
                ->with('error', 'WhatsApp nömrəsi təyin edilməyib və ya səbət boşdur.');
        }

        return redirect()->away($url);
    }

    /**
     * @return array{success: bool, message: string, data: array<string, mixed>}
     */
    private function envelope(array $payload): array
    {
        return [
            'success' => (bool) ($payload['success'] ?? false),
            'message' => (string) ($payload['message'] ?? ''),
            'data' => (array) ($payload['data'] ?? []),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    private function total(array $items): float
    {
        return array_reduce(
            $items,
            fn (float $carry, array $item) => $carry + (float) ($item['retail_total'] ?? 0),
            0.0
        );
    }
}
