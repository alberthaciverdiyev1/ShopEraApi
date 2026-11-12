<?php

namespace App\Services\Storefront;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Delivery\Services\DeliveryService;
use Modules\Delivery\Services\PickupPointService;
use Modules\Order\Http\Requests\OrderAddRequest;
use Modules\Order\Http\Requests\PreviewOrderRequest;
use Modules\Order\Services\OrderService;

/**
 * Checkout reads and the order placement for the Blade storefront. Every call
 * goes through the same module requests/services the mobile API uses, so the
 * validation rules and totals stay identical.
 */
class CheckoutService
{
    public function __construct(
        private readonly OrderService $orders,
        private readonly DeliveryService $delivery,
        private readonly PickupPointService $pickupPoints,
        private readonly AccountService $account,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function addresses(): array
    {
        if (! Auth::check()) {
            return [];
        }

        return $this->account->addresses();
    }

    /**
     * @return array<int, array{key: string, name: string}>
     */
    public function cities(): array
    {
        return $this->account->cities();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function pickupPoints(): array
    {
        $request = Request::create('/api/pickup-point', 'GET', ['is_active' => 1]);

        return $this->pickupPoints->getAll($request)->getData(true)['data'] ?? [];
    }

    /**
     * @return array{success: bool, message: string, data: array<string, mixed>}
     */
    public function preview(array $data): array
    {
        $request = PreviewOrderRequest::create('/api/order/preview', 'GET', $data);
        $request->setContainer(app())->setUserResolver(fn () => Auth::user());
        $request->validateResolved();

        return $this->envelope($this->orders->previewOrder($request)->getData(true));
    }

    /**
     * @return array{success: bool, message: string, data: array<string, mixed>}
     */
    public function place(array $data): array
    {
        $request = OrderAddRequest::create('/api/order', 'POST', $data);
        $request->setContainer(app())->setUserResolver(fn () => Auth::user());
        $request->validateResolved();

        return $this->envelope($this->orders->orderFromBasket($request)->getData(true));
    }

    /**
     * WhatsApp deep link prefilled with the current basket. The link is built
     * by the order module so the storefront and the API stay identical.
     */
    public function whatsappUrl(): ?string
    {
        $request = Request::create('/api/order/whatsapp-link', 'GET');
        $request->setUserResolver(fn () => Auth::user());

        return $this->orders->whatsappLink($request)->getData(true)['data']['url'] ?? null;
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
}
