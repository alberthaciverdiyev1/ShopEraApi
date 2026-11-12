<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\StoreVendorRequest;
use App\Support\Concerns\InteractsWithModuleControllers;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Modules\Marketplace\Http\Controllers\ListingController as ModuleListingController;
use Modules\Marketplace\Http\Controllers\VendorController;

class StorePanelController extends Controller
{
    use InteractsWithModuleControllers;

    public function panel(): View|RedirectResponse
    {
        $vendor = $this->payload(
            app(VendorController::class)->mine($this->moduleHttpRequest())
        );

        if (! is_array($vendor) || $vendor === []) {
            return redirect()->route('storefront.store.open');
        }

        $listings = app(ModuleListingController::class)->myListings($this->moduleHttpRequest());

        return view('storefront.store-panel.panel', [
            'vendor' => $vendor,
            'payload' => $vendor,
            'listings' => $listings instanceof ResourceCollection ? $listings->resolve() : (array) $this->payload($listings),
        ]);
    }

    public function open(): View|RedirectResponse
    {
        $vendor = $this->payload(
            app(VendorController::class)->mine($this->moduleHttpRequest())
        );

        if (is_array($vendor) && $vendor !== []) {
            return redirect()->route('storefront.store.panel');
        }

        return view('storefront.store-panel.open');
    }

    public function create(StoreVendorRequest $request): RedirectResponse
    {
        app(VendorController::class)->create(
            $this->moduleHttpRequest($request->validated(), 'POST', $request->allFiles())
        );

        return redirect()
            ->route('storefront.store.panel')
            ->with('status', 'Mağazanız yaradıldı.');
    }

    public function update(StoreVendorRequest $request): RedirectResponse
    {
        $vendor = $this->payload(
            app(VendorController::class)->mine($this->moduleHttpRequest())
        );

        if (! is_array($vendor) || $vendor === []) {
            return redirect()->route('storefront.store.open');
        }

        app(VendorController::class)->update(
            $this->moduleHttpRequest($request->validated(), 'PUT', $request->allFiles())
        );

        return redirect()
            ->route('storefront.store.panel')
            ->with('status', 'Mağaza məlumatları yeniləndi.');
    }
}
