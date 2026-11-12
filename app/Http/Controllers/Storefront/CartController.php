<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\UpdateCartItemRequest;
use App\Services\Storefront\CartService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * Basket page. Not linked from the navigation: the marketplace flow is
 * listing-based, so the cart stays reachable only by its own URL.
 */
class CartController extends Controller
{
    public function __construct(private readonly CartService $cart) {}

    public function index(): View
    {
        $items = $this->cart->items();

        return view('storefront.cart.index', [
            'items' => $items,
            'total' => $this->cart->total($items),
        ]);
    }

    public function update(UpdateCartItemRequest $request, int $id): RedirectResponse
    {
        $result = $this->cart->update($id, (int) $request->validated('quantity'));

        return redirect()
            ->route('storefront.cart')
            ->with(
                $result['success'] ? 'status' : 'error',
                $result['success'] ? 'Səbət yeniləndi.' : ($result['message'] ?: 'Səbət yenilənə bilmədi.')
            );
    }

    public function destroy(int $id): RedirectResponse
    {
        $result = $this->cart->remove($id);

        return redirect()
            ->route('storefront.cart')
            ->with(
                $result['success'] ? 'status' : 'error',
                $result['success'] ? 'Məhsul səbətdən çıxarıldı.' : ($result['message'] ?: 'Məhsul çıxarıla bilmədi.')
            );
    }
}
