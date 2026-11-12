<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\UpdateCartItemRequest;
use App\Support\Concerns\InteractsWithModuleControllers;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Modules\User\Http\Controllers\BasketController;
use Modules\User\Http\Requests\Basket\BasketUpdateRequest;

/**
 * Basket page. Not linked from the navigation: the marketplace flow is
 * listing-based, so the cart stays reachable only by its own URL.
 */
class CartController extends Controller
{
    use InteractsWithModuleControllers;

    public function index(): View
    {
        $items = (array) $this->payload(
            app(BasketController::class)->getAll($this->moduleHttpRequest())
        );

        return view('storefront.cart.index', [
            'items' => $items,
            'total' => $this->total($items),
        ]);
    }

    public function update(UpdateCartItemRequest $request, int $id): RedirectResponse
    {
        $form = $this->moduleRequest(BasketUpdateRequest::class, [
            'quantity' => max(1, (int) $request->validated('quantity')),
        ], 'PUT');

        $payload = app(BasketController::class)->update($id, $form)->getData(true);
        $success = (bool) ($payload['success'] ?? false);
        $message = (string) ($payload['message'] ?? '');

        return redirect()
            ->route('storefront.cart')
            ->with(
                $success ? 'status' : 'error',
                $success ? 'Səbət yeniləndi.' : ($message ?: 'Səbət yenilənə bilmədi.')
            );
    }

    public function destroy(int $id): RedirectResponse
    {
        $payload = app(BasketController::class)->delete($id)->getData(true);
        $success = (bool) ($payload['success'] ?? false);
        $message = (string) ($payload['message'] ?? '');

        return redirect()
            ->route('storefront.cart')
            ->with(
                $success ? 'status' : 'error',
                $success ? 'Məhsul səbətdən çıxarıldı.' : ($message ?: 'Məhsul çıxarıla bilmədi.')
            );
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
