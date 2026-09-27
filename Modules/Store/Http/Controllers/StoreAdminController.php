<?php

namespace Modules\Store\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Modules\Product\Http\Entities\Product;
use Modules\Setting\Services\SettingService;
use Modules\Store\Http\Entities\Store;
use Modules\Store\Http\Entities\StoreOrderFulfillment;
use Modules\Product\Http\Resources\ProductResource;
use Modules\Store\Http\Resources\StoreOrderItemResource;
use Modules\Store\Http\Resources\StoreResource;
use Modules\Store\Services\StoreOrderItemLoader;
use Modules\Store\Http\Entities\MarketplaceAuditLog;
use Modules\Store\Http\Entities\StoreOrderSettlement;
use Modules\Store\Http\Entities\StoreWithdrawal;
use Modules\Store\Services\MarketplaceAuditService;
use Modules\Store\Services\MerchantOrderService;
use Modules\Store\Services\StoreBalanceService;
use Modules\Store\Services\StoreWalletService;
use Modules\Store\Services\StoreWithdrawalService;
use Symfony\Component\HttpFoundation\Response as StatusCode;
use Modules\Notification\Services\NotificationService;
use Modules\Store\Support\StoreLabels;

class StoreAdminController extends Controller
{
    public function __construct(
        private StoreWalletService $wallet,
        private StoreOrderItemLoader $orderItems,
        private StoreWithdrawalService $withdrawals,
        private StoreBalanceService $balances,
        private MarketplaceAuditService $audit,
        private MerchantOrderService $merchantOrders,
    ) {
        $this->middleware('permission:view users')->only([
            'index', 'show', 'document', 'fulfillments', 'products', 'showProduct',
            'pendingProducts', 'settlements', 'withdrawals', 'auditLogs', 'deletionPreview',
        ]);
        $this->middleware('permission:update user')->only([
            'updateStatus', 'updateTrust', 'adjustWallet', 'markHandedOver',
            'approveWithdrawal', 'payWithdrawal', 'rejectWithdrawal', 'refundSettlement',
            'destroy',
        ]);
        $this->middleware('permission:update product')->only(['updateProductApproval']);
    }

    /**
     * Every product waiting for review, across all stores. Without this the admin
     * has to open each store in turn to discover there is anything to approve.
     */
    public function pendingProducts(Request $request)
    {
        $query = Product::with(['images', 'category', 'brand', 'store:id,name,logo_path,is_trusted'])
            ->whereNotNull('store_id')
            ->where('approval_status', $request->input('approval_status', 'pending'))
            // By when it landed in the queue, not when the product was first
            // created. Sorting by created_at floated an edited three-month-old
            // product to the top of today's queue, which is half of why it read
            // as a stale duplicate.
            ->oldest('updated_at');

        if ($request->filled('store_id')) {
            $query->where('store_id', $request->integer('store_id'));
        }

        // Lets the admin work through first-time submissions and re-approvals
        // separately — they are different jobs.
        if ($request->filled('resubmitted')) {
            $request->boolean('resubmitted')
                ? $query->whereNotNull('last_approved_at')
                : $query->whereNull('last_approved_at');
        }

        if ($request->filled('search')) {
            filterLike($query, ['title', 'sku'], $request->all());
        }

        $items = $query->paginate(min(max((int) $request->input('per_page', 20), 1), 100));

        return response()->json([
            'success' => true,
            'message' => __('Store products retrieved successfully.'),
            'data' => ProductResource::collection($items)->response()->getData(true),
            'counts' => [
                'pending' => Product::whereNotNull('store_id')->where('approval_status', 'pending')->count(),
                'resubmitted' => Product::whereNotNull('store_id')->where('approval_status', 'pending')
                    ->whereNotNull('last_approved_at')->count(),
                'rejected' => Product::whereNotNull('store_id')->where('approval_status', 'rejected')->count(),
                'approved' => Product::whereNotNull('store_id')->where('approval_status', 'approved')->count(),
            ],
        ]);
    }

    /** Sub-orders: one row per store per order, with the money and the goods. */
    public function settlements(Request $request)
    {
        $query = StoreOrderSettlement::with([
            'store:id,name,user_id',
            'order:id,transaction_id,user_id,created_at',
        ])->latest();

        foreach (['status' => 'status', 'store_id' => 'store_id', 'payment_type' => 'payment_type'] as $param => $column) {
            if ($request->filled($param)) {
                $query->where($column, $request->input($param));
            }
        }

        if ($request->filled('order_id')) {
            $query->where('order_id', $request->integer('order_id'));
        }

        $items = $query->paginate(min(max((int) $request->input('per_page', 20), 1), 100));

        $orderIds = collect($items->items())->pluck('order_id')->filter()->unique()->values()->all();
        $storeIds = collect($items->items())->pluck('store_id')->filter()->unique()->values()->all();
        $grouped = $this->orderItems->forOrders($orderIds, $storeIds);

        foreach ($items->items() as $row) {
            $rowItems = $grouped->get($row->order_id, collect())
                ->filter(fn ($item) => (int) $item->product?->store_id === (int) $row->store_id);
            $row->setAttribute('items', StoreOrderItemResource::collection($rowItems)->resolve());
            $row->setAttribute('status_label', StoreLabels::settlementStatus($row->status));
            $row->setAttribute('payment_type_label', StoreLabels::paymentType($row->payment_type));
            $row->setAttribute('refundable_amount',
                round((float) $row->net_amount - (float) $row->refunded_amount, 2));
        }

        return responseHelper(__('Store sales retrieved successfully.'), 200, $items);
    }

    /** Partial return: give back part of a store's earnings on one order. */
    public function refundSettlement(Request $request, int $id)
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $settlement = StoreOrderSettlement::with('store')->findOrFail($id);

        return responseHelper(__('Refund recorded successfully.'), 200,
            $this->merchantOrders->refundSettlement($settlement, (float) $data['amount'], $data['note'] ?? null));
    }

    public function withdrawals(Request $request)
    {
        $query = StoreWithdrawal::with(['store:id,name,user_id', 'reviewer:id,name,surname', 'payer:id,name,surname'])
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('store_id')) {
            $query->where('store_id', $request->integer('store_id'));
        }

        $items = $query->paginate(min(max((int) $request->input('per_page', 20), 1), 100));

        foreach ($items->items() as $row) {
            $row->setAttribute('status_label', StoreLabels::withdrawalStatus($row->status));
        }

        return response()->json([
            'success' => true,
            'message' => __('Withdrawal requests retrieved successfully.'),
            'data' => $items,
            'counts' => [
                'pending' => StoreWithdrawal::where('status', 'pending')->count(),
                'approved' => StoreWithdrawal::where('status', 'approved')->count(),
            ],
        ]);
    }

    public function approveWithdrawal(Request $request, int $id)
    {
        return responseHelper(__('Withdrawal request approved.'), 200,
            $this->withdrawals->approve(StoreWithdrawal::with('store')->findOrFail($id), $request->user()->id));
    }

    public function payWithdrawal(Request $request, int $id)
    {
        $data = $request->validate(['admin_note' => ['nullable', 'string', 'max:1000']]);

        return responseHelper(__('Withdrawal marked as paid.'), 200,
            $this->withdrawals->markPaid(
                StoreWithdrawal::with('store')->findOrFail($id),
                $request->user()->id,
                $data['admin_note'] ?? null,
            ));
    }

    public function rejectWithdrawal(Request $request, int $id)
    {
        $data = $request->validate(['rejection_reason' => ['required', 'string', 'max:1000']]);

        return responseHelper(__('Withdrawal request rejected.'), 200,
            $this->withdrawals->reject(
                StoreWithdrawal::with('store')->findOrFail($id),
                $request->user()->id,
                $data['rejection_reason'],
            ));
    }

    public function auditLogs(Request $request)
    {
        $query = MarketplaceAuditLog::with('user:id,name,surname')->latest();

        foreach (['action', 'subject_type'] as $param) {
            if ($request->filled($param)) {
                $query->where($param, $request->input($param));
            }
        }
        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->integer('subject_id'));
        }

        return responseHelper(__('Audit logs retrieved successfully.'), 200,
            $query->paginate(min(max((int) $request->input('per_page', 30), 1), 100)));
    }

    public function index(Request $request)
    {
        $query = Store::with('user')->withCount('products')->latest();
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('owner_full_name', 'like', "%{$search}%")
                ->orWhereHas('user', fn ($u) => $u->where('phone', 'like', "%{$search}%")));
        }
        $stores = $query->paginate(min(max((int) $request->input('per_page', 20), 1), 100));

        return response()->json([
            'success' => true,
            'data' => StoreResource::collection($stores),
            'meta' => [
                'current_page' => $stores->currentPage(), 'last_page' => $stores->lastPage(),
                'per_page' => $stores->perPage(), 'total' => $stores->total(),
            ],
        ]);
    }

    public function show(int $id)
    {
        $store = Store::with(['user', 'walletTransactions' => fn ($q) => $q->latest()->limit(50)])
            ->withCount('products')->findOrFail($id);
        $data = StoreResource::make($store)->resolve();
        $data['products_count'] = $store->products_count;
        $data['wallet_transactions'] = $store->walletTransactions;
        $data['identity_documents'] = [
            'front' => route('api.store.admin.identity-document', ['id' => $store->id, 'side' => 'front']),
            'back' => route('api.store.admin.identity-document', ['id' => $store->id, 'side' => 'back']),
        ];

        return responseHelper(__('Store retrieved successfully.'), 200, $data);
    }

    /**
     * What removing this store would take with it.
     *
     * Deleting a shop is the one action in this panel nobody can undo from
     * the screen, so the numbers are shown first and the answer also says
     * what would stay: orders and money movements are records, not the
     * shop's property.
     */
    public function deletionPreview(int $id)
    {
        $store = Store::withCount('products')->findOrFail($id);

        $productIds = Product::withTrashed()->where('store_id', $store->id)->pluck('id');

        $orderItems = DB::table('order_items')->whereIn('product_id', $productIds);
        $orderIds = (clone $orderItems)->distinct()->pluck('order_id');

        $openFulfillments = $store->fulfillments()
            ->whereIn('status', ['awaiting', 'accepted', 'ready'])
            ->count();

        $pendingWithdrawals = $store->withdrawals()
            ->whereIn('status', ['pending', 'approved'])
            ->count();

        $heldSettlements = $store->settlements()->where('status', 'pending')->count();

        // Each of these is a reason to stop and look rather than a rule that
        // forbids the deletion: the admin decides, with the numbers in front
        // of them.
        $warnings = [];

        if ($openFulfillments > 0) {
            $warnings[] = __(':count order(s) are still waiting to be handed over.', ['count' => $openFulfillments]);
        }

        if ($pendingWithdrawals > 0) {
            $warnings[] = __(':count withdrawal request(s) are still open.', ['count' => $pendingWithdrawals]);
        }

        if ($heldSettlements > 0) {
            $warnings[] = __(':count earning(s) have not been released yet.', ['count' => $heldSettlements]);
        }

        if ((float) $store->balance != 0.0) {
            $warnings[] = __('The wallet balance is :amount ₼.', ['amount' => number_format((float) $store->balance, 2)]);
        }

        return responseHelper('Operation successful.', StatusCode::HTTP_OK, [
            'store' => [
                'id' => $store->id,
                'name' => $store->name,
                'status' => $store->status,
                'balance' => (float) $store->balance,
            ],
            // Goes away with the store.
            'will_be_removed' => [
                'products' => $productIds->count(),
                'active_products' => Product::where('store_id', $store->id)->count(),
            ],
            // Stays, because it is a record of something that happened.
            'will_be_kept' => [
                'orders' => $orderIds->count(),
                'order_items' => (clone $orderItems)->count(),
                'settlements' => $store->settlements()->count(),
                'wallet_transactions' => $store->walletTransactions()->count(),
                'withdrawals' => $store->withdrawals()->count(),
                'fulfillments' => $store->fulfillments()->count(),
            ],
            'warnings' => $warnings,
        ]);
    }

    /**
     * Removes the shop and its products from the app.
     *
     * Both are soft deletes: an order placed months ago still has to be able
     * to show what was bought and from whom, and the money history stays
     * whole. The owner keeps their customer account.
     */
    public function destroy(Request $request, int $id)
    {
        $store = Store::findOrFail($id);
        $productIds = Product::where('store_id', $store->id)->pluck('id');

        DB::transaction(function () use ($store, $productIds) {
            Product::whereIn('id', $productIds)->delete();

            $store->update(['is_active' => false, 'status' => 'suspended']);
            $store->delete();
        });

        $this->audit->log('store.deleted', $store, [
            'name' => $store->name,
            'products' => $productIds->count(),
            'balance' => (float) $store->balance,
        ]);

        return responseHelper('Operation successful.', StatusCode::HTTP_OK, [
            'deleted_products' => $productIds->count(),
        ]);
    }

    public function updateStatus(Request $request, int $id)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['pending', 'changes_requested', 'approved', 'rejected', 'suspended'])],
            'rejection_reason' => ['nullable', 'string', 'required_if:status,rejected'],
            'changes_requested_reason' => ['nullable', 'string', 'required_if:status,changes_requested'],
            'suspension_reason' => ['nullable', 'string', 'required_if:status,suspended'],
        ]);

        $store = Store::findOrFail($id);
        $before = $store->only(['status', 'is_active', 'deactivated_reason']);
        $status = $data['status'];
        $approved = $status === 'approved';
        $canActivate = $approved && (float) $store->balance > -$this->negativeLimit($store);

        $store->update([
            'status' => $status,
            'is_active' => $canActivate,
            'approved_by' => $approved ? $request->user()->id : $store->approved_by,
            'approved_at' => $approved ? now() : $store->approved_at,
            'rejected_at' => $status === 'rejected' ? now() : null,
            'rejection_reason' => $status === 'rejected' ? $data['rejection_reason'] : null,
            'changes_requested_at' => $status === 'changes_requested' ? now() : null,
            'changes_requested_reason' => $status === 'changes_requested' ? $data['changes_requested_reason'] : null,
            'suspended_at' => $status === 'suspended' ? now() : null,
            'suspension_reason' => $status === 'suspended' ? $data['suspension_reason'] : null,
            'deactivated_reason' => $approved
                ? ($canActivate ? null : 'negative_balance_limit')
                : ($status === 'suspended' ? 'suspended' : 'admin_status'),
        ]);

        $this->audit->logDiff('store.status_changed', $store, $before,
            $store->only(['status', 'is_active', 'deactivated_reason']));

        $this->notifyStoreStatus($store, $status, $data);

        return responseHelper(__('Store status updated successfully.'), 200, StoreResource::make($store->fresh('user')));
    }

    /**
     * Tells the merchant what happened to their application. Notification
     * problems never block the status change itself.
     */
    private function notifyStoreStatus(Store $store, string $status, array $data): void
    {
        if (! $store->user_id) {
            return;
        }

        [$title, $body] = match ($status) {
            'approved' => [__('Mağazanız təsdiqləndi'), __('Artıq məhsul əlavə edə bilərsiniz.')],
            'rejected' => [__('Mağaza müraciətiniz rədd edildi'), __('Səbəb: :reason', ['reason' => $data['rejection_reason'] ?? __('Göstərilməyib')])],
            'changes_requested' => [__('Mağaza müraciətinizdə düzəliş tələb olunur'), __('Səbəb: :reason', ['reason' => $data['changes_requested_reason'] ?? __('Göstərilməyib')])],
            'suspended' => [__('Mağazanız dayandırıldı'), __('Səbəb: :reason', ['reason' => $data['suspension_reason'] ?? __('Göstərilməyib')])],
            default => [__('Mağaza statusunuz dəyişdi'), __('Müraciətiniz yenidən yoxlanılır.')],
        };

        try {
            app(NotificationService::class)->add([
                'title' => $title,
                'body' => $body,
                'user_id' => $store->user_id,
                'data' => [
                    'type' => 'store_status_changed',
                    'status' => $status,
                    'store_id' => (string) $store->id,
                ],
            ]);
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    public function updateTrust(Request $request, int $id)
    {
        $data = $request->validate(['is_trusted' => ['required', 'boolean']]);
        $store = Store::where('status', 'approved')->findOrFail($id);
        $store->update(['is_trusted' => $data['is_trusted']]);
        $this->audit->log('store.trust_changed', $store, ['is_trusted' => (bool) $data['is_trusted']]);

        return responseHelper(__('Store trust status updated successfully.'), 200, StoreResource::make($store));
    }

    public function adjustWallet(Request $request, int $id)
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'not_in:0'],
            'operation' => ['required', Rule::in(['top_up', 'withdrawal', 'adjustment'])],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);
        $amount = abs((float) $data['amount']);
        if ($data['operation'] === 'withdrawal' || ($data['operation'] === 'adjustment' && (float) $data['amount'] < 0)) {
            $amount *= -1;
        }
        $store = Store::findOrFail($id);
        $transaction = $this->wallet->add(
            $store, $amount, $data['operation'], $data['note'] ?? null, null, $request->user()->id
        );
        $this->audit->log('store.wallet_adjusted', $store, [
            'operation' => $data['operation'],
            'amount' => $amount,
            'balance_after' => (float) $transaction->balance_after,
        ]);

        return responseHelper(__('Store balance updated successfully.'), 200, $transaction);
    }

    public function updateProductApproval(Request $request, int $productId)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['approved', 'rejected'])],
            'rejection_reason' => ['nullable', 'string', 'required_if:status,rejected'],
        ]);
        $product = Product::with('store')->whereNotNull('store_id')->findOrFail($productId);
        $approved = $data['status'] === 'approved';
        $product->update([
            'approval_status' => $data['status'],
            'approved_by' => $approved ? $request->user()->id : null,
            'approved_at' => $approved ? now() : null,
            // Survives a rejection. Without this the "already approved once"
            // badge would vanish the first time an admin says no — precisely
            // the loop the sellers were stuck in.
            'last_approved_at' => $approved ? now() : $product->last_approved_at,
            'rejection_reason' => $approved ? null : $data['rejection_reason'],
            'is_active' => $approved && $product->store?->is_active,
        ]);

        $this->audit->log('product.approval_changed', $product, [
            'status' => $data['status'],
            'store_id' => $product->store_id,
        ]);
        $this->notifyMerchant($product, $approved, $data['rejection_reason'] ?? null);

        $fresh = Product::withTrashed()
            ->with(['images', 'videos', 'colors', 'sizes', 'category', 'brand', 'store'])
            ->findOrFail($product->id);

        return responseHelper(__('Product approval updated successfully.'), 200, ProductResource::make($fresh));
    }

    public function products(Request $request, int $id)
    {
        Store::findOrFail($id);
        $query = Product::withTrashed()
            ->with(['images', 'videos', 'colors', 'sizes', 'category', 'brand'])
            ->where('store_id', $id)
            ->latest();
        if ($request->filled('approval_status')) {
            $query->where('approval_status', $request->input('approval_status'));
        }

        $products = $query->paginate(min(max((int) $request->input('per_page', 50), 1), 100));

        return responseHelper(__('Store products retrieved successfully.'), 200,
            ProductResource::collection($products)->response()->getData(true));
    }

    /**
     * Full detail of a single store product, so the admin can judge it before
     * approving instead of deciding from the title alone.
     */
    public function showProduct(int $productId)
    {
        $product = Product::withTrashed()
            ->with(['images', 'videos', 'colors', 'sizes', 'category', 'brand', 'store', 'user'])
            ->whereNotNull('store_id')
            ->findOrFail($productId);

        return responseHelper(__('Store product retrieved successfully.'), 200, ProductResource::make($product));
    }

    public function fulfillments(Request $request)
    {
        $query = StoreOrderFulfillment::with(['store:id,name,user_id', 'order:id,transaction_id,user_id,created_at'])->latest();
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $items = $query->paginate(min(max((int) $request->input('per_page', 20), 1), 100));

        $orderIds = collect($items->items())->pluck('order_id')->filter()->unique()->values()->all();
        $storeIds = collect($items->items())->pluck('store_id')->filter()->unique()->values()->all();
        $grouped = $this->orderItems->forOrders($orderIds, $storeIds);

        foreach ($items->items() as $row) {
            $rowItems = $grouped->get($row->order_id, collect())
                ->filter(fn ($item) => (int) $item->product?->store_id === (int) $row->store_id);
            $row->setAttribute('items', StoreOrderItemResource::collection($rowItems)->resolve());
            $row->setAttribute('items_count', $rowItems->sum('quantity'));
            $row->setAttribute('status_label', StoreLabels::fulfillmentStatus($row->status));
        }

        return responseHelper(__('Store fulfillments retrieved successfully.'), 200, $items);
    }

    public function markHandedOver(Request $request, int $id)
    {
        $data = $request->validate(['note' => ['nullable', 'string', 'max:1000']]);
        $fulfillment = StoreOrderFulfillment::findOrFail($id);
        $fulfillment->update([
            'status' => 'handed_over', 'handed_over_at' => now(),
            'handled_by' => $request->user()->id, 'note' => $data['note'] ?? $fulfillment->note,
        ]);

        $this->merchantOrders->recordSubOrderStatus(
            $fulfillment->store_id, $fulfillment->order_id, 'handed_over', $data['note'] ?? null
        );
        $this->audit->log('fulfillment.handed_over', $fulfillment, ['order_id' => $fulfillment->order_id]);

        return responseHelper(__('Order handover marked successfully.'), 200, $fulfillment->fresh());
    }

    public function document(Request $request, int $id, string $side)
    {
        abort_unless(in_array($side, ['front', 'back'], true), 404);
        $store = Store::findOrFail($id);
        $path = $side === 'front' ? $store->identity_front_path : $store->identity_back_path;
        abort_unless(Storage::exists($path), 404);

        return Storage::response($path);
    }

    /**
     * Let the merchant know the outcome of the review. Notification problems must
     * never block the approval itself.
     */
    private function notifyMerchant(Product $product, bool $approved, ?string $reason): void
    {
        $userId = $product->store?->user_id;
        if (! $userId) {
            return;
        }

        $title = ucfirst((string) $product->title);

        try {
            app(NotificationService::class)->add([
                'title' => $approved ? __('Məhsulunuz təsdiqləndi') : __('Məhsulunuz imtina edildi'),
                'body' => $approved
                    ? __('":name" adlı məhsulunuz təsdiqləndi və mağazanızda satışa çıxdı.', ['name' => $title])
                    : __('":name" adlı məhsulunuz imtina edildi. Səbəb: :reason', ['name' => $title, 'reason' => $reason ?: __('Göstərilməyib')]),
                'user_id' => $userId,
                'data' => [
                    'type' => $approved ? 'store_product_approved' : 'store_product_rejected',
                    'product_id' => (string) $product->id,
                    'store_id' => (string) $product->store_id,
                ],
            ]);
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    private function negativeLimit(Store $store): float
    {
        return (float) ($store->negative_balance_limit_override
            ?? app(SettingService::class)->getStoreNegativeBalanceLimit());
    }
}
