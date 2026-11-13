<?php

namespace App\Http\Controllers\Web;

use App\Core\Concerns\InteractsWithModuleControllers;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Modules\User\Http\Controllers\FavoritesController;

class WishlistController extends Controller
{
    use InteractsWithModuleControllers;

    public function index(): View
    {
        return view('storefront.wishlist.index', [
            'products' => $this->payload(
                app(FavoritesController::class)->list($this->moduleHttpRequest())
            ),
        ]);
    }

    public function destroy(string $slug): RedirectResponse
    {
        $id = $this->productIdBySlug($slug);
        abort_if($id === null, 404);

        app(FavoritesController::class)->delete($id);

        return redirect()
            ->route('storefront.wishlist')
            ->with('status', 'Məhsul istək siyahısından çıxarıldı.');
    }
}
