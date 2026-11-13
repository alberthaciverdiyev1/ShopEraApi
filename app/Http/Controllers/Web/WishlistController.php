<?php

namespace App\Http\Controllers\Web;

use App\Core\Concerns\InteractsWithModuleControllers;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

    /**
     * Toggle a listing in the signed-in user's favorites. The same call backs
     * the heart buttons on product cards and the product page.
     */
    public function store(Request $request, string $slug): JsonResponse|RedirectResponse
    {
        $id = $this->productIdBySlug($slug);
        abort_if($id === null, 404);

        $payload = app(FavoritesController::class)->add($id)->getData(true);
        $success = (bool) ($payload['success'] ?? false);
        $favorited = str_contains(strtolower((string) ($payload['message'] ?? '')), 'added');

        if ($request->expectsJson()) {
            return response()->json([
                'success' => $success,
                'favorited' => $favorited,
                'message' => $payload['message'] ?? '',
            ], $success ? 200 : 422);
        }

        return back()->with('status', $favorited
            ? 'Elan istək siyahısına əlavə edildi.'
            : 'Elan istək siyahısından çıxarıldı.');
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
