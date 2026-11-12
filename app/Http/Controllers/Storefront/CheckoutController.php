<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\PlaceOrderRequest;
use App\Http\Requests\Storefront\StoreAddressRequest;
use App\Services\Storefront\CartService;
use App\Services\Storefront\CheckoutService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\User\Services\AddressService;

/**
 * Checkout page. Not linked from the navigation: the marketplace flow is
 * listing-based, so it stays reachable only by its own URL.
 */
class CheckoutController extends Controller
{
    public function __construct(
        private readonly CheckoutService $checkout,
        private readonly CartService $cart,
        private readonly AddressService $addresses,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $items = $this->cart->items();
        $addresses = $this->checkout->addresses();

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
                $result = $this->checkout->preview(array_filter([
                    'address_type' => $deliveryType,
                    'address_type_id' => $addressTypeId,
                    'promo_code' => $promoCode ?: null,
                ], fn ($value) => $value !== null));

                if ($result['success']) {
                    $preview = $result['data'];
                } else {
                    $previewError = $result['message'];
                }
            }
        }

        return view('storefront.checkout.index', [
            'items' => $items,
            'total' => $this->cart->total($items),
            'addresses' => $addresses,
            'cities' => $this->checkout->cities(),
            'pickupPoints' => $this->checkout->pickupPoints(),
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
        $response = $this->addresses->add($request);
        $payload = $response->getData(true);

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
        $result = $this->checkout->place($request->validated());

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
        $url = $this->checkout->whatsappUrl();

        if (! $url) {
            return redirect()
                ->route('storefront.checkout')
                ->with('error', 'WhatsApp nömrəsi təyin edilməyib və ya səbət boşdur.');
        }

        return redirect()->away($url);
    }
}
