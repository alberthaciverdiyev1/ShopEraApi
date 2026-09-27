<?php

namespace Modules\Store\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Setting\Services\SettingService;
use Modules\Store\Http\Entities\Store;
use Modules\Store\Http\Entities\StoreOrderSettlement;
use Modules\Store\Http\Entities\StoreWalletTransaction;
use Modules\Store\Http\Entities\StoreWithdrawal;

/**
 * Answers the money questions about a store.
 *
 * `stores.balance` is the withdrawable figure and the only stored total. Money
 * that is earned but still cancellable lives in settlements that are settled
 * and not yet released, so it is derived here rather than cached — a second
 * stored total would only be one bug away from disagreeing with the ledger.
 *
 * Cash orders never release: the merchant collected the cash directly, so only
 * the commission moves, and it moves immediately.
 */
class StoreBalanceService
{
    /** Earned but not yet withdrawable. */
    public function pending(Store $store): float
    {
        return (float) StoreOrderSettlement::where('store_id', $store->id)
            ->where('status', 'settled')
            ->whereNull('released_at')
            ->where('payment_type', '!=', 'CASH')
            ->sum(DB::raw('net_amount - refunded_amount'));
    }

    /** Held by withdrawal requests that are open, so it cannot be requested twice. */
    public function reserved(Store $store): float
    {
        return (float) StoreWithdrawal::where('store_id', $store->id)->open()->sum('amount');
    }

    /** What the merchant may actually request right now. */
    public function withdrawable(Store $store): float
    {
        return round(max(0, (float) $store->balance - $this->reserved($store)), 2);
    }

    /**
     * The seller balance screen described in the proposal.
     */
    public function metrics(Store $store): array
    {
        $settlements = StoreOrderSettlement::where('store_id', $store->id)
            ->whereIn('status', ['settled', 'released']);

        $monthStart = Carbon::now()->startOfMonth();

        $thisMonth = (clone $settlements)->where('created_at', '>=', $monthStart);
        $transactions = StoreWalletTransaction::where('store_id', $store->id);

        return [
            'balance' => (float) $store->balance,
            'withdrawable' => $this->withdrawable($store),
            'pending' => round($this->pending($store), 2),
            'reserved' => round($this->reserved($store), 2),
            'month' => [
                'gross' => round((float) (clone $thisMonth)->sum('gross_amount'), 2),
                'net' => round((float) (clone $thisMonth)->sum('net_amount'), 2),
                'orders' => (clone $thisMonth)->count(),
            ],
            'total' => [
                'gross' => round((float) (clone $settlements)->sum('gross_amount'), 2),
                'net' => round((float) (clone $settlements)->sum('net_amount'), 2),
                'orders' => (clone $settlements)->count(),
            ],
            'commission_total' => round((float) (clone $settlements)->sum('commission_amount'), 2),
            'refunded_total' => round((float) (clone $settlements)->sum('refunded_amount'), 2),
            'penalty_total' => round(abs((float) (clone $transactions)
                ->where('type', 'late_handover_penalty')->sum('amount')), 2),
            'paid_out_total' => round(abs((float) (clone $transactions)
                ->whereIn('type', ['withdrawal_paid', 'withdrawal'])->sum('amount')), 2),
            'min_withdrawal' => (float) app(SettingService::class)->getStoreMinWithdrawalAmount(),
        ];
    }
}
