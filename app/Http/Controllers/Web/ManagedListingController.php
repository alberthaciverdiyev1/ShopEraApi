<?php

namespace App\Http\Controllers\Web;

use App\Core\Concerns\InteractsWithModuleControllers;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\UpdateManagedListingRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Modules\Color\Http\Controllers\ColorController;
use Modules\Marketplace\Http\Controllers\ListingController as ModuleListingController;
use Modules\Marketplace\Http\Requests\UpdateListingRequest;
use Modules\Size\Http\Controllers\SizeController;

/**
 * Secret-link management for guest listings: no account required, the token
 * in the URL is the credential, matching the API's /listings/manage routes.
 */
class ManagedListingController extends Controller
{
    use InteractsWithModuleControllers;

    public function show(string $token): View
    {
        if (session()->pull('listing_deleted') === $token) {
            return view('storefront.listings.manage-deleted');
        }

        $listing = $this->payload(
            app(ModuleListingController::class)->manageShow($token)
        );

        abort_if(! is_array($listing) || $listing === [], 404);

        $categoryId = (int) ($listing['category_id'] ?? 0);

        return view('storefront.listings.manage', [
            'token' => $token,
            'listing' => $listing,
            // The edit form reuses the create form's colour and size pickers.
            'colors' => (array) $this->payload(
                app(ColorController::class)->list($this->moduleHttpRequest(['per_page' => 200]))
            ),
            'sizes' => $categoryId > 0
                ? (array) $this->payload(app(SizeController::class)->list(
                    $this->moduleHttpRequest(['per_page' => 200, 'category_id' => $categoryId])
                ))
                : [],
        ]);
    }

    public function update(UpdateManagedListingRequest $request, string $token): RedirectResponse
    {
        $form = $this->moduleRequest(
            UpdateListingRequest::class,
            $request->validated(),
            'PUT',
            $request->allFiles()
        );

        app(ModuleListingController::class)->manageUpdate($form, $token);

        return redirect()
            ->route('storefront.listings.manage', $token)
            ->with('status', 'Elan yeniləndi.');
    }

    public function destroy(string $token): RedirectResponse
    {
        app(ModuleListingController::class)->manageDestroy($token);

        return redirect()
            ->route('storefront.listings.manage', $token)
            ->with('listing_deleted', $token);
    }
}
