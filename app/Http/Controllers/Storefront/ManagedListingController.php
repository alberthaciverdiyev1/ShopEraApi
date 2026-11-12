<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\UpdateManagedListingRequest;
use App\Services\Storefront\SellerService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Modules\Marketplace\Http\Resources\ListingResource;

/**
 * Secret-link management for guest listings: no account required, the token
 * in the URL is the credential, matching the API's /listings/manage routes.
 */
class ManagedListingController extends Controller
{
    public function __construct(private readonly SellerService $seller) {}

    public function show(string $token): View
    {
        if (session()->pull('listing_deleted') === $token) {
            return view('storefront.listings.manage-deleted');
        }

        $listing = $this->seller->findManaged($token);

        return view('storefront.listings.manage', [
            'token' => $token,
            'listing' => ListingResource::make($listing->load('images', 'city'))->resolve(),
        ]);
    }

    public function update(UpdateManagedListingRequest $request, string $token): RedirectResponse
    {
        $listing = $this->seller->findManaged($token);
        $this->seller->updateManaged($listing, $request->validated(), $request);

        return redirect()
            ->route('storefront.listings.manage', $token)
            ->with('status', 'Elan yeniləndi.');
    }

    public function destroy(string $token): RedirectResponse
    {
        $this->seller->deleteManaged($this->seller->findManaged($token));

        return redirect()
            ->route('storefront.listings.manage', $token)
            ->with('listing_deleted', $token);
    }
}
