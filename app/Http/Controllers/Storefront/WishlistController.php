<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Services\Storefront\WishlistService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class WishlistController extends Controller
{
    public function __construct(private readonly WishlistService $wishlist) {}

    public function index(): View
    {
        return view('storefront.wishlist.index', [
            'products' => $this->wishlist->products(),
        ]);
    }

    public function destroy(int $id): RedirectResponse
    {
        $this->wishlist->remove($id);

        return redirect()
            ->route('storefront.wishlist')
            ->with('status', 'Məhsul istək siyahısından çıxarıldı.');
    }
}
