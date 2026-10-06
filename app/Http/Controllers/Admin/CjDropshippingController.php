<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Modules\CjDropShopping\Jobs\RunCjSync;
use Modules\CjDropShopping\Services\DAuthService;

/**
 * Admin page for the CJ Dropshipping integration. Syncing runs on the queue so
 * a slow provider call never blocks the request; the page shows the cached
 * result of the last run. Gated by the `cj_dropshipping` feature via the
 * AdminMenu + EnforceAdminMenuAccess middleware.
 */
class CjDropshippingController extends AdminController
{
    protected string $title = 'CJ Dropshipping';

    /**
     * Individually triggerable syncs. Add a resource here (and a route/action)
     * to expose a new button; "sync all" picks them up automatically.
     *
     * @var array<string,array{label:string,description:string,route:string}>
     */
    private const ACTIONS = [
        'categories' => [
            'label' => 'Kateqoriyalar',
            'description' => 'CJ kategoriya ağacını çəkib lokal kateqoriyalara yazır.',
            'route' => 'admin.cj-dropshipping.sync-categories',
        ],
        'products' => [
            'label' => 'Məhsullar (CJ panelindən)',
            'description' => 'CJ paneldə əlavə etdiyin məhsulları lokal kataloqa çəkir.',
            'route' => 'admin.cj-dropshipping.sync-products',
        ],
    ];

    public function index()
    {
        return view('admin.pages.cj-dropshipping', [
            'title' => $this->title,
            'actions' => self::ACTIONS,
            'configured' => app(DAuthService::class)->isConfigured(),
            'lastResult' => Cache::get(RunCjSync::resultKey()),
        ]);
    }

    public function syncCategories(Request $request): RedirectResponse
    {
        return $this->dispatch('categories', $request);
    }

    public function syncProducts(Request $request): RedirectResponse
    {
        return $this->dispatch('products', $request);
    }

    public function syncAll(Request $request): RedirectResponse
    {
        return $this->dispatch('all', $request);
    }

    private function dispatch(string $resource, Request $request): RedirectResponse
    {
        RunCjSync::dispatch($resource, $request->boolean('translate'));

        return back()->with('status', __('CJ sinxronizasiyası arxa planda başladı. Nəticə bir neçə dəqiqə ərzində görünəcək.'));
    }
}
