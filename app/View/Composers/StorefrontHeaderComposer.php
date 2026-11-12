<?php

namespace App\View\Composers;

use App\Support\Concerns\InteractsWithModuleControllers;
use Illuminate\View\View;
use Modules\User\Http\Controllers\FavoritesController;

/**
 * Supplies the header's category menu and wishlist preview. Both come from
 * the module controllers, never from a query written in the view.
 */
class StorefrontHeaderComposer
{
    use InteractsWithModuleControllers;

    public function compose(View $view): void
    {
        $user = auth()->user();
        $wishlist = [];

        if ($user) {
            $wishlist = array_slice(
                (array) $this->payload(
                    app(FavoritesController::class)->list($this->moduleHttpRequest())
                ),
                0,
                4
            );
        }

        $view->with([
            'categories' => $this->storefrontCategories(),
            'wishlist' => $wishlist,
            'user' => $user,
        ]);
    }
}
