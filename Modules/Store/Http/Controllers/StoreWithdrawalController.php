<?php

namespace Modules\Store\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Store\Http\Entities\Store;
use Modules\Store\Http\Entities\StoreWithdrawal;
use Modules\Store\Services\StoreBalanceService;
use Modules\Store\Services\StoreWithdrawalService;
use Modules\Store\Support\StoreLabels;

/** Merchant side of the withdrawal flow. */
class StoreWithdrawalController extends Controller
{
    public function __construct(
        private StoreWithdrawalService $withdrawals,
        private StoreBalanceService $balances,
    ) {}

    public function index(Request $request)
    {
        $store = $this->ownStore($request);
        $items = StoreWithdrawal::where('store_id', $store->id)->latest()
            ->paginate(min(max((int) $request->input('per_page', 20), 1), 100));

        foreach ($items->items() as $row) {
            $row->setAttribute('status_label', StoreLabels::withdrawalStatus($row->status));
        }

        return responseHelper(__('Withdrawal requests retrieved successfully.'), 200, $items);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01', 'max:100000'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $withdrawal = $this->withdrawals->request(
            $this->ownStore($request),
            (float) $data['amount'],
            $data['note'] ?? null,
        );

        return responseHelper(__('Withdrawal request created successfully.'), 201, $withdrawal);
    }

    public function cancel(Request $request, int $id)
    {
        $store = $this->ownStore($request);
        $withdrawal = StoreWithdrawal::where('store_id', $store->id)->findOrFail($id);

        return responseHelper(__('Withdrawal request cancelled.'), 200, $this->withdrawals->cancel($withdrawal));
    }

    /** The seller balance screen described in the proposal. */
    public function balance(Request $request)
    {
        return responseHelper(__('Balance retrieved successfully.'), 200,
            $this->balances->metrics($this->ownStore($request)));
    }

    private function ownStore(Request $request): Store
    {
        return Store::where('user_id', $request->user()->id)->firstOrFail();
    }
}
