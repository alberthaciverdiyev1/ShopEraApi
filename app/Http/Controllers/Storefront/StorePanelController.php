<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\StoreVendorRequest;
use App\Services\Storefront\SellerService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Modules\Marketplace\Services\VendorService;

class StorePanelController extends Controller
{
    public function __construct(
        private readonly VendorService $vendors,
        private readonly SellerService $seller,
    ) {}

    public function panel(): View|RedirectResponse
    {
        $vendor = $this->vendors->store(request()->user());

        if (! $vendor) {
            return redirect()->route('storefront.store.open');
        }

        return view('storefront.store-panel.panel', [
            'vendor' => $vendor,
            'payload' => $this->vendors->payload($vendor),
            'listings' => $this->seller->mine(request()->user()),
        ]);
    }

    public function open(): View|RedirectResponse
    {
        if ($this->vendors->store(request()->user())) {
            return redirect()->route('storefront.store.panel');
        }

        return view('storefront.store-panel.open');
    }

    public function create(StoreVendorRequest $request): RedirectResponse
    {
        $this->vendors->create($request->user(), $request->validated(), $request);

        return redirect()
            ->route('storefront.store.panel')
            ->with('status', 'Mağazanız yaradıldı.');
    }

    public function update(StoreVendorRequest $request): RedirectResponse
    {
        $vendor = $this->vendors->store($request->user());

        if (! $vendor) {
            return redirect()->route('storefront.store.open');
        }

        $this->vendors->update($vendor, $request->validated(), $request);

        return redirect()
            ->route('storefront.store.panel')
            ->with('status', 'Mağaza məlumatları yeniləndi.');
    }
}
