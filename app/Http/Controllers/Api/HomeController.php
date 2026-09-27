<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Live\Http\Resources\LiveStreamListResource;
use Modules\Live\Services\LiveStreamService;
use Modules\Live\Support\CatalogueProducts;
use Modules\Product\Http\Entities\Product;
use Modules\Product\Http\Resources\ProductResource;
use Modules\Setting\Services\SettingService;
use Modules\Store\Http\Entities\Store;

/**
 * The rebuilt home screen in one call: TeymurStore's own catalogue on top,
 * the marketplace stores below it.
 *
 * This is a new endpoint rather than a change to /api/product or
 * /api/category/with-products. Those are what the builds already on the App
 * Store and Google Play call on every launch, and reshaping their responses
 * would break the app in customers' hands. Nothing here touches them.
 */
class HomeController extends Controller
{
    public function __construct(private LiveStreamService $liveStreams)
    {
    }

    public function index(Request $request)
    {
        $productLimit = min(max((int) $request->input('product_limit', 20), 1), 50);
        $storeLimit = min(max((int) $request->input('store_limit', 12), 1), 50);
        $perStoreProducts = min(max((int) $request->input('store_product_limit', 6), 1), 20);

        return responseHelper('Home retrieved successfully.', 200, [
            'live' => $this->live(),
            'house_products' => $this->houseProducts($request, $productLimit),
            'stores' => $this->stores($storeLimit, $perStoreProducts, $request),
        ]);
    }

    /** Null rather than an error when nothing is on air. */
    private function live(): ?array
    {
        $stream = $this->liveStreams->current();

        return $stream ? (new LiveStreamListResource($stream))->resolve() : null;
    }

    /**
     * The house catalogue — products TeymurStore sells itself, which is what
     * `store_id IS NULL` means here.
     */
    private function houseProducts(Request $request, int $limit): array
    {
        $products = CatalogueProducts::load(Product::query()->publiclyAvailable()->whereNull('store_id'))
            ->orderByDesc('is_pinned')
            ->latest('id')
            ->limit($limit)
            ->get();

        CatalogueProducts::decorate($products);

        return ProductResource::collection($products)->resolve($request);
    }

    /**
     * The marketplace half. Skipped entirely when the admin kill switch is off,
     * so the home screen matches what the catalogue is already willing to show.
     *
     * @return array<int, array<string, mixed>>
     */
    private function stores(int $limit, int $perStoreProducts, Request $request): array
    {
        if (! app(SettingService::class)->isMarketplaceEnabled()) {
            return [];
        }

        $stores = Store::query()
            ->where('status', 'approved')
            ->where('is_active', true)
            ->limit($limit)
            ->get(['id', 'name', 'about', 'logo_path', 'is_trusted']);

        if ($stores->isEmpty()) {
            return [];
        }

        // Which products to show is decided on two columns, then only those
        // are loaded in full. Every store's whole catalogue with seven
        // relations each would be the heaviest query on the home screen.
        $shown = Product::query()
            ->publiclyAvailable()
            ->whereIn('store_id', $stores->pluck('id'))
            ->orderByDesc('is_pinned')
            ->latest('id')
            ->get(['id', 'store_id'])
            ->groupBy('store_id')
            ->flatMap(fn ($rows) => $rows->take($perStoreProducts)->pluck('id'));

        $loaded = CatalogueProducts::load(Product::query()->whereIn('id', $shown))->get()->keyBy('id');
        CatalogueProducts::decorate($loaded);

        $products = $shown->map(fn ($id) => $loaded->get($id))->filter()->groupBy('store_id');

        $counts = Product::query()
            ->publiclyAvailable()
            ->whereIn('store_id', $stores->pluck('id'))
            ->select('store_id', DB::raw('count(*) as total'))
            ->groupBy('store_id')
            ->pluck('total', 'store_id');

        return $stores->map(fn (Store $store) => [
            'id' => $store->id,
            'name' => $store->name,
            'about' => $store->about,
            'logo_path' => $store->logo_path,
            'is_trusted' => (bool) $store->is_trusted,
            'products_count' => (int) ($counts[$store->id] ?? 0),
            'products' => ProductResource::collection(
                ($products[$store->id] ?? collect())->take($perStoreProducts)
            )->resolve($request),
        ])->values()->all();
    }
}
