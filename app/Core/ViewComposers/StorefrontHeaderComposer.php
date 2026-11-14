<?php

namespace App\Core\ViewComposers;

use App\Core\Concerns\InteractsWithModuleControllers;
use Illuminate\View\View;
use Modules\Setting\Http\Controllers\SocialLinkController;
use Modules\User\Http\Controllers\FavoritesController;

/**
 * Supplies the header's category menu, wishlist preview and footer-style
 * social icons. All come from the module controllers, never from a query
 * written in the view.
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

        $social = (array) $this->payload(
            app(SocialLinkController::class)->index()
        );

        // The dark strip only carries the networks the admin marked for it.
        $socialLinks = array_values(array_filter(
            (array) ($social['links'] ?? []),
            fn ($link) => ($link['show_in_header'] ?? false) === true
        ));

        $view->with([
            'categories' => $this->storefrontCategories(),
            'wishlist' => $wishlist,
            'user' => $user,
            'socialLinks' => $socialLinks,
        ]);
    }
}
