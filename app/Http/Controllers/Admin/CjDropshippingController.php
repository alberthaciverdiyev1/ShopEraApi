<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\CjDropShopping\Services\DAuthService;
use Modules\CjDropShopping\Services\DCategoryService;
use RuntimeException;
use Throwable;

/**
 * Admin page for the CJ Dropshipping integration. Every provider sync is
 * triggered manually here, one button per resource, plus a "sync everything"
 * button. The page is gated by the `cj_dropshipping` feature via the
 * AdminMenu + EnforceAdminMenuAccess middleware.
 */
class CjDropshippingController extends AdminController
{
    protected string $title = 'CJ Dropshipping';

    /**
     * Individually triggerable syncs. Add a resource here (and to handler())
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
    ];

    public function index()
    {
        return view('admin.pages.cj-dropshipping', [
            'title' => $this->title,
            'actions' => self::ACTIONS,
            'configured' => app(DAuthService::class)->isConfigured(),
            'lastResult' => session('cj_result'),
        ]);
    }

    public function syncCategories(Request $request): RedirectResponse
    {
        return $this->run(fn () => $this->handler('categories', $this->translate($request)));
    }

    public function syncAll(Request $request): RedirectResponse
    {
        return $this->run(function () use ($request) {
            $translate = $this->translate($request);
            $results = [];

            foreach (array_keys(self::ACTIONS) as $key) {
                $results[$key] = $this->handler($key, $translate);
            }

            return $results;
        });
    }

    /** @return array<string,mixed> */
    private function handler(string $key, bool $translate): array
    {
        return match ($key) {
            'categories' => app(DCategoryService::class)->sync($translate),
            default => [],
        };
    }

    private function translate(Request $request): bool
    {
        return $request->boolean('translate');
    }

    private function run(callable $callback): RedirectResponse
    {
        try {
            // Resolve the result first: back()->with() flashes immediately, so
            // building the success redirect before the work would flash a false
            // "completed" message even when the sync throws.
            $result = $callback();

            return back()
                ->with('status', __('CJ sinxronizasiyası tamamlandı.'))
                ->with('cj_result', $result);
        } catch (RuntimeException $e) {
            return back()->withErrors(['cj' => $e->getMessage()]);
        } catch (Throwable $e) {
            Log::error('CJ Dropshipping admin sync failed', ['message' => $e->getMessage()]);

            return back()->withErrors(['cj' => __('CJ sinxronizasiyası alınmadı.')]);
        }
    }
}
