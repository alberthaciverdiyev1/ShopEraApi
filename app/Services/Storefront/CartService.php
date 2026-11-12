<?php

namespace App\Services\Storefront;

use Illuminate\Support\Facades\Auth;
use Modules\User\Http\Requests\Basket\BasketUpdateRequest;
use Modules\User\Services\BasketService;

/**
 * Signed-in basket for the storefront cart page. The module service already
 * works off the session user, so no token plumbing is needed.
 */
class CartService
{
    public function __construct(private readonly BasketService $baskets) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function items(): array
    {
        if (! Auth::check()) {
            return [];
        }

        return $this->baskets->getAll(request())->getData(true)['data'] ?? [];
    }

    public function total(array $items): float
    {
        return array_reduce(
            $items,
            fn (float $carry, array $item) => $carry + (float) ($item['retail_total'] ?? 0),
            0.0
        );
    }

    /**
     * @return array{success: bool, message: string}
     */
    public function update(int $id, int $quantity): array
    {
        $request = BasketUpdateRequest::create('/api/basket/'.$id, 'PUT', [
            'quantity' => max(1, $quantity),
        ]);
        $request->setContainer(app())->setUserResolver(fn () => Auth::user());
        $request->validateResolved();

        return $this->envelope($this->baskets->update($id, $request)->getData(true));
    }

    /**
     * @return array{success: bool, message: string}
     */
    public function remove(int $id): array
    {
        return $this->envelope($this->baskets->delete($id)->getData(true));
    }

    /**
     * @return array{success: bool, message: string}
     */
    private function envelope(array $payload): array
    {
        return [
            'success' => (bool) ($payload['success'] ?? true),
            'message' => (string) ($payload['message'] ?? ''),
        ];
    }
}
