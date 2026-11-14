<?php

namespace App\Core\ViewComposers;

use App\Core\Concerns\InteractsWithModuleControllers;
use Illuminate\View\View;
use Modules\Setting\Http\Controllers\FooterLinkController;
use Modules\Setting\Http\Controllers\SocialLinkController;

/**
 * Supplies the admin-managed footer columns and social icons. The links live
 * in the Setting module and are read through its controllers, never queried
 * in the view.
 */
class StorefrontFooterComposer
{
    use InteractsWithModuleControllers;

    public function compose(View $view): void
    {
        $data = (array) $this->payload(
            app(FooterLinkController::class)->index()
        );

        $social = (array) $this->payload(
            app(SocialLinkController::class)->index()
        );

        $view->with([
            'footerGroups' => (array) ($data['groups'] ?? []),
            'footerSections' => (array) ($data['sections'] ?? []),
            'socialLinks' => (array) ($social['links'] ?? []),
        ]);
    }
}
