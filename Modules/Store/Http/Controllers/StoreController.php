<?php

namespace Modules\Store\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Modules\Store\Http\Entities\Store;
use Modules\Store\Http\Resources\StoreResource;
use Modules\Store\Http\Resources\StoreOrderItemResource;
use Modules\Store\Http\Resources\StoreWalletTransactionResource;
use Modules\Store\Services\StoreOrderItemLoader;
use Modules\Store\Services\StoreService;
use Modules\Store\Support\StoreLabels;

class StoreController extends Controller
{
    public function __construct(
        private StoreService $service,
        private StoreOrderItemLoader $orderItems,
    ) {}

    public function instructions()
    {
        return responseHelper(__('Seller instructions retrieved successfully.'), 200, $this->service->instructions());
    }

    public function register(Request $request)
    {
        return responseHelper(__('Store application submitted successfully.'), 201, StoreResource::make($this->service->register($request)));
    }

    public function profile(Request $request)
    {
        $store = Store::with('user')->where('user_id', $request->user()->id)->first();

        return $store
            ? responseHelper(__('Store retrieved successfully.'), 200, StoreResource::make($store))
            : responseHelper(__('Store not found.'), 404);
    }

    public function wallet(Request $request)
    {
        $store = Store::where('user_id', $request->user()->id)->firstOrFail();
        $history = $store->walletTransactions()->latest()->paginate(min(max((int) $request->input('per_page', 20), 1), 100));

        return response()->json([
            'success' => true,
            'data' => [
                'balance' => (float) $store->balance,
                'transactions' => StoreWalletTransactionResource::collection($history),
            ],
            'meta' => [
                'current_page' => $history->currentPage(),
                'last_page' => $history->lastPage(),
                'per_page' => $history->perPage(),
                'total' => $history->total(),
            ],
        ]);
    }

    public function sales(Request $request)
    {
        $store = Store::where('user_id', $request->user()->id)->firstOrFail();
        $sales = $store->settlements()->visibleToSeller()
            ->with('order:id,transaction_id,payment_type,created_at')
            ->latest()->paginate(min(max((int) $request->input('per_page', 20), 1), 100));

        $this->attachItems($sales, $store->id);

        return responseHelper(__('Store sales retrieved successfully.'), 200, $sales);
    }

    public function fulfillments(Request $request)
    {
        $store = Store::where('user_id', $request->user()->id)->firstOrFail();
        $items = $store->fulfillments()->with('order:id,transaction_id,created_at')
            ->latest()->paginate(min(max((int) $request->input('per_page', 20), 1), 100));

        $this->attachItems($items, $store->id);

        return responseHelper(__('Store fulfillments retrieved successfully.'), 200, $items);
    }

    /**
     * Adds the purchased lines to every row of a settlement/fulfillment page.
     * Added as an extra key, so older clients that ignore it keep working.
     */
    private function attachItems($paginator, ?int $storeId): void
    {
        $orderIds = collect($paginator->items())->pluck('order_id')->filter()->unique()->values()->all();
        $grouped = $this->orderItems->forOrders($orderIds, $storeId);

        foreach ($paginator->items() as $row) {
            $rowItems = $grouped->get($row->order_id, collect());
            $row->setAttribute('items', StoreOrderItemResource::collection($rowItems)->resolve());
            $row->setAttribute('items_count', $rowItems->sum('quantity'));
            $row->setAttribute('status_label', $row->getTable() === 'store_order_fulfillments'
                ? StoreLabels::fulfillmentStatus($row->status)
                : StoreLabels::settlementStatus($row->status));
            if ($row->getAttribute('payment_type') !== null) {
                $row->setAttribute('payment_type_label', StoreLabels::paymentType($row->payment_type));
            }
        }
    }

    /** The store's own history for one order — never the customer's full order. */
    public function orderStatuses(Request $request, int $orderId)
    {
        $store = Store::where('user_id', $request->user()->id)->firstOrFail();

        $rows = \Modules\Store\Http\Entities\StoreOrderStatus::where('store_id', $store->id)
            ->where('order_id', $orderId)
            ->oldest('id')
            ->get()
            ->map(fn ($row) => [
                'id' => $row->id,
                'status' => $row->status,
                'status_label' => StoreLabels::subOrderStatus($row->status),
                'note' => $row->note,
                'created_at' => $row->created_at?->toIso8601String(),
            ]);

        return responseHelper(__('Status history retrieved successfully.'), 200, $rows);
    }

    public function identityDocument(Request $request, string $side)
    {
        abort_unless(in_array($side, ['front', 'back'], true), 404);
        $store = Store::where('user_id', $request->user()->id)->firstOrFail();
        $path = $side === 'front' ? $store->identity_front_path : $store->identity_back_path;
        abort_unless(Storage::exists($path), 404);

        return Storage::response($path);
    }
}
