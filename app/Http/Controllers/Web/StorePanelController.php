<?php

namespace App\Http\Controllers\Web;

use App\Core\Concerns\InteractsWithModuleControllers;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\StoreVendorRequest;
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

        $permissions = (array) ($vendor['permissions'] ?? []);
        $hasPermission = fn (string $key): bool => in_array($key, $permissions, true);

        $defaultTab = $hasPermission('listings')
            ? 'listings'
            : ($hasPermission('settings') ? 'settings' : 'overview');

        $tab = request()->query('tab', $defaultTab);
        $tab = in_array($tab, ['overview', 'listings', 'promoted', 'settings'], true) ? $tab : $defaultTab;

        // Members without the matching permission only see the overview.
        if (($tab === 'listings' && ! $hasPermission('listings'))
            || ($tab === 'promoted' && ! $hasPermission('listings'))
            || ($tab === 'settings' && ! $hasPermission('settings'))) {
            $tab = 'overview';
        }

        return view('storefront.store-panel.panel', [
            'vendor' => $vendor,
            'payload' => $vendor,
            'tab' => $tab,
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
            ->route('storefront.store.panel', ['tab' => 'settings'])
            ->with('status', 'Mağaza məlumatları yeniləndi.');
    }
}
