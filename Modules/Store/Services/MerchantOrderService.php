<?php

namespace Modules\Store\Services;

use App\Enums\OrderStatus;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Notification\Services\NotificationService;
use Modules\Order\Http\Entities\Order;
use Modules\Product\Services\ProductPricingService;
use Modules\Setting\Services\SettingService;
use Modules\Store\Http\Entities\Store;
use Modules\Store\Http\Entities\StoreOrderFulfillment;
use Modules\Store\Http\Entities\StoreOrderSettlement;
use Modules\Store\Http\Entities\StoreOrderStatus;

class MerchantOrderService
{
    public function __construct(
        private StoreWalletService $wallet,
        private NotificationService $notifications,
        private MarketplaceAuditService $audit,
    ) {}

    public function assertCanPlaceOrder(
        Collection $basketItems,
        string $paymentType,
        ?string $pricingType = null,
        ?float $actualOrderTotal = null,
    ): void {
        $groups = $basketItems->filter(fn ($item) => $item->product?->store_id)
            ->groupBy(fn ($item) => $item->product->store_id);

        $allItemsGross = $basketItems->sum(fn ($item) => $this->basketItemTotal($item, $pricingType));

        foreach ($groups as $storeId => $items) {
            $store = Store::find($storeId);
            if (! $store || $store->status !== 'approved' || ! $store->is_active) {
                throw ValidationException::withMessages([
                    'store' => 'Səbətdə hazırda sifariş qəbul etməyən mağazaya aid məhsul var.',
                ]);
            }

            if (strtoupper($paymentType) === 'CASH') {
                $gross = $items->sum(fn ($item) => $this->basketItemTotal($item, $pricingType));
                if ($actualOrderTotal !== null && $allItemsGross > 0) {
                    $gross = round($actualOrderTotal * ($gross / $allItemsGross), 2);
                }
                $commission = round($gross * $this->commissionPercent($store) / 100, 2);
                $limit = (float) ($store->negative_balance_limit_override
                    ?? app(SettingService::class)->getStoreNegativeBalanceLimit());

                if (((float) $store->balance - $commission) < -$limit) {
                    throw ValidationException::withMessages([
                        'store_balance' => 'Mağazanın balans limiti bu nağd sifarişi qəbul etməyə imkan vermir.',
                    ]);
                }
            }
        }
    }

    public function registerOrder(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $lockedOrder = Order::with('items.product.store')->whereKey($order->id)->lockForUpdate()->firstOrFail();
            $grouped = $lockedOrder->items->filter(fn ($item) => $item->product?->store_id)
                ->groupBy(fn ($item) => $item->product->store_id);
            $allItemsTotal = (float) $lockedOrder->items->sum('total_price');

            foreach ($grouped as $storeId => $items) {
                $store = Store::findOrFail($storeId);
                $rawGross = (float) $items->sum('total_price');
                $gross = $allItemsTotal > 0
                    ? round((float) $lockedOrder->total_price * ($rawGross / $allItemsTotal), 2)
                    : 0;
                $commissionPercent = $this->commissionPercent($store);
                $commission = round($gross * $commissionPercent / 100, 2);
                $net = round($gross - $commission, 2);
                $paymentType = strtoupper((string) ($lockedOrder->payment_type ?: 'CARD'));

                $settlement = StoreOrderSettlement::firstOrCreate(
                    ['store_id' => $store->id, 'order_id' => $lockedOrder->id],
                    [
                        'payment_type' => $paymentType,
                        'gross_amount' => $gross,
                        'commission_percent' => $commissionPercent,
                        'commission_amount' => $commission,
                        'net_amount' => $net,
                        'status' => 'pending',
                    ]
                );

                if ($paymentType === 'CASH') {
                    $this->settleCash($settlement);
                    $this->ensureFulfillment($settlement);
                } elseif ($paymentType === 'BALANCE' || $lockedOrder->paid_at) {
                    $this->settleNonCash($settlement);
                    $this->ensureFulfillment($settlement);
                }
                // CARD orders stay pending until PaymentService::settlePaidOrder()
                // confirms the payment.
            }
        });
    }

    public function settlePaidOrder(Order $order): void
    {
        $this->registerOrder($order->fresh());

        StoreOrderSettlement::where('order_id', $order->id)
            ->where('status', 'pending')
            ->get()
            ->each(function (StoreOrderSettlement $settlement) {
                $this->settleNonCash($settlement);
                $this->ensureFulfillment($settlement);
            });
    }

    /**
     * Undoes a store's share of an order. Used for cancellations and for full
     * returns — the proposal requires both, and only cancellation was wired up
     * before.
     *
     * What has to move back depends on where the money currently is:
     *   - cash sale: only the commission was ever debited, so credit it back
     *   - card sale already released: the net was credited, so debit it back
     *   - card sale still pending: nothing moved, so nothing has to move back
     */
    public function reverseOrder(Order $order, string $reason = 'cancelled'): void
    {
        StoreOrderSettlement::with('store')
            ->where('order_id', $order->id)
            ->whereIn('status', ['pending', 'settled'])
            ->get()
            ->each(function (StoreOrderSettlement $settlement) use ($reason) {
                $delta = $this->reversalAmount($settlement);

                if ($delta !== 0.0) {
                    $this->wallet->add(
                        $settlement->store,
                        $delta,
                        'order_reversal',
                        $reason === 'returned'
                            ? __('Sifariş #:id geri qaytarıldı', ['id' => $settlement->order_id])
                            : __('Sifariş #:id ləğv edildi', ['id' => $settlement->order_id]),
                        $settlement->order_id,
                        auth()->id(),
                        "store-order-reversal:{$settlement->id}",
                    );
                }

                $settlement->update([
                    'status' => 'reversed',
                    'reversed_at' => now(),
                    'refunded_amount' => (float) $settlement->net_amount,
                ]);

                StoreOrderFulfillment::where('store_id', $settlement->store_id)
                    ->where('order_id', $settlement->order_id)
                    ->whereIn('status', ['awaiting', 'overdue'])
                    ->update(['status' => 'cancelled']);

                $this->recordSubOrderStatus(
                    $settlement->store_id,
                    $settlement->order_id,
                    $reason === 'returned' ? 'returned' : 'cancelled',
                );

                $this->audit->log('settlement.reversed', $settlement, [
                    'reason' => $reason,
                    'amount' => $delta,
                ]);

                // Money left the merchant's balance; saying nothing meant they
                // only found out by noticing the number had changed.
                $this->notifyStoreOwner(
                    $settlement->store,
                    $reason === 'returned' ? __('Sifariş geri qaytarıldı') : __('Sifariş ləğv edildi'),
                    $delta < 0.0
                        ? __('#:order nömrəli sifariş üzrə :amount AZN balansınızdan geri alındı.', [
                            'order' => $settlement->order_id,
                            'amount' => number_format(abs($delta), 2),
                        ])
                        : __('#:order nömrəli sifariş bağlandı, balansınıza təsir etmədi.', [
                            'order' => $settlement->order_id,
                        ]),
                    [
                        'type' => 'store_order_reversed',
                        'reason' => $reason,
                        'order_id' => (string) $settlement->order_id,
                        'store_id' => (string) $settlement->store_id,
                    ],
                );
            });
    }

    /**
     * Partial return: reverses only part of a store's earnings on an order,
     * leaving the rest settled.
     */
    public function refundSettlement(StoreOrderSettlement $settlement, float $amount, ?string $note = null): StoreOrderSettlement
    {
        $amount = round($amount, 2);
        $alreadyRefunded = (float) $settlement->refunded_amount;
        $remaining = round((float) $settlement->net_amount - $alreadyRefunded, 2);

        if ($amount <= 0 || $amount > $remaining) {
            throw ValidationException::withMessages([
                'amount' => __('Qaytarıla bilən məbləğ :amount AZN-dir.', ['amount' => number_format(max($remaining, 0), 2)]),
            ]);
        }

        return DB::transaction(function () use ($settlement, $amount, $note, $alreadyRefunded) {
            // Money only has to leave the balance if it already reached it.
            if ($settlement->released_at && strtoupper((string) $settlement->payment_type) !== 'CASH') {
                $this->wallet->add(
                    $settlement->store,
                    -$amount,
                    'order_refund',
                    __('Sifariş #:id üzrə qismən qaytarma', ['id' => $settlement->order_id]),
                    $settlement->order_id,
                    auth()->id(),
                    "store-partial-refund:{$settlement->id}:" . round($alreadyRefunded + $amount, 2),
                    ['refunded_total' => round($alreadyRefunded + $amount, 2)],
                    false,
                    $note,
                );
            }

            $settlement->update(['refunded_amount' => round($alreadyRefunded + $amount, 2)]);

            $this->recordSubOrderStatus($settlement->store_id, $settlement->order_id, 'partially_returned', $note);
            $this->audit->log('settlement.partially_refunded', $settlement, ['amount' => $amount]);

            $this->notifyStoreOwner(
                $settlement->store,
                __('Qismən geri qaytarma'),
                __('#:order nömrəli sifariş üzrə :amount AZN geri qaytarıldı.', [
                    'order' => $settlement->order_id,
                    'amount' => number_format($amount, 2),
                ]) . ($note ? ' ' . __('Səbəb: :reason', ['reason' => $note]) : ''),
                [
                    'type' => 'store_order_partially_refunded',
                    'order_id' => (string) $settlement->order_id,
                    'store_id' => (string) $settlement->store_id,
                ],
            );

            return $settlement->fresh();
        });
    }

    private function reversalAmount(StoreOrderSettlement $settlement): float
    {
        if ($settlement->status !== 'settled') {
            return 0.0;
        }

        $isCash = strtoupper((string) $settlement->payment_type) === 'CASH';

        if ($isCash) {
            // The commission was taken up front; give it back.
            return round((float) $settlement->commission_amount, 2);
        }

        if (! $settlement->released_at) {
            return 0.0;
        }

        return -round((float) $settlement->net_amount - (float) $settlement->refunded_amount, 2);
    }

    /** Appends a line to the store's own view of an order's history. */
    public function recordSubOrderStatus(int $storeId, int $orderId, string $status, ?string $note = null): void
    {
        try {
            StoreOrderStatus::create([
                'store_id' => $storeId,
                'order_id' => $orderId,
                'status' => $status,
                'note' => $note,
                'created_by' => auth()->id(),
            ]);
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    public function processOverdueFulfillments(): int
    {
        $count = 0;
        StoreOrderFulfillment::with('store')
            ->whereIn('status', ['awaiting', 'overdue'])
            ->whereNull('handed_over_at')
            ->where('handover_due_at', '<=', now())
            ->chunkById(100, function ($fulfillments) use (&$count) {
                foreach ($fulfillments as $fulfillment) {
                    $penalty = app(SettingService::class)->getStoreLatePenaltyAmount();
                    $fulfillment->status = $penalty > 0 ? 'penalized' : 'overdue';

                    if ($penalty > 0 && ! $fulfillment->penalized_at) {
                        $this->wallet->add(
                            $fulfillment->store,
                            -$penalty,
                            'late_handover_penalty',
                            "Sifariş #{$fulfillment->order_id} 24 saat ərzində təhvil verilmədi",
                            $fulfillment->order_id,
                            null,
                            "store-handover-penalty:{$fulfillment->id}",
                        );
                        $fulfillment->penalty_amount = $penalty;
                        $fulfillment->penalized_at = now();

                        try {
                            $this->notifications->add([
                                'title' => 'Mağaza sifarişinin təhvili gecikib',
                                'body' => "#{$fulfillment->order_id} sifarişi vaxtında təhvil verilmədiyi üçün {$penalty} AZN cərimə tətbiq edildi.",
                                'user_id' => $fulfillment->store->user_id,
                                'data' => [
                                    'type' => 'store_handover_penalty',
                                    'order_id' => (string) $fulfillment->order_id,
                                    'store_id' => (string) $fulfillment->store_id,
                                ],
                            ]);
                        } catch (\Throwable $exception) {
                            report($exception);
                        }
                    }

                    $fulfillment->save();
                    $count++;
                }
            });

        return $count;
    }

    /**
     * The negative-balance gate is assertCanPlaceOrder(), which runs before the
     * order exists. Re-enforcing it here would let a cent of rounding drift abort
     * an order the customer has already paid for; StoreWalletService::add() still
     * deactivates the store when the balance crosses the limit, so no further
     * orders get through.
     */
    /**
     * Warns merchants whose hand-over deadline is close but not yet passed.
     * Sent once per fulfillment; the penalty run afterwards is a separate step.
     *
     * @return int number of reminders sent
     */
    public function sendHandoverReminders(int $hoursBefore = 4): int
    {
        $sent = 0;

        StoreOrderFulfillment::with('store')
            ->where('status', 'awaiting')
            ->whereNull('handed_over_at')
            ->whereNull('reminded_at')
            ->where('handover_due_at', '>', now())
            ->where('handover_due_at', '<=', now()->addHours($hoursBefore))
            ->chunkById(100, function ($fulfillments) use (&$sent) {
                foreach ($fulfillments as $fulfillment) {
                    $remaining = (int) max(1, now()->diffInHours($fulfillment->handover_due_at));

                    $this->notifyStoreOwner(
                        $fulfillment->store,
                        __('Təhvil vaxtı yaxınlaşır'),
                        __('#:order nömrəli sifarişi təhvil verməyə :hours saat qalıb.', [
                            'order' => $fulfillment->order_id,
                            'hours' => $remaining,
                        ]),
                        [
                            'type' => 'store_handover_reminder',
                            'order_id' => (string) $fulfillment->order_id,
                            'store_id' => (string) $fulfillment->store_id,
                        ],
                    );

                    $fulfillment->update(['reminded_at' => now()]);
                    $sent++;
                }
            });

        return $sent;
    }

    private function settleCash(StoreOrderSettlement $settlement): void
    {
        if ($settlement->status !== 'pending') {
            return;
        }

        $transaction = null;
        if ((float) $settlement->commission_amount > 0) {
            $transaction = $this->wallet->add(
                $settlement->store,
                -(float) $settlement->commission_amount,
                'cash_sale_commission',
                "Nağd sifariş #{$settlement->order_id} komissiyası",
                $settlement->order_id,
                null,
                "store-cash-commission:{$settlement->id}",
                ['commission_percent' => (float) $settlement->commission_percent],
            );
        }
        $settlement->update([
            'status' => 'settled',
            'wallet_transaction_id' => $transaction?->id,
            'settled_at' => now(),
        ]);
    }

    /**
     * Card/balance sale. The money is earned but not withdrawable yet — the order
     * can still be cancelled or returned — so nothing is credited here. The
     * settlement sits `settled` with released_at null, which is what
     * StoreBalanceService reports as pending, until releaseEligibleSettlements()
     * moves it across.
     */
    private function settleNonCash(StoreOrderSettlement $settlement): void
    {
        if ($settlement->status !== 'pending') {
            return;
        }

        $settlement->update([
            'status' => 'settled',
            'settled_at' => now(),
        ]);
    }

    /**
     * Moves earned money into the withdrawable balance once the order is
     * delivered and the hold period configured by the admin has elapsed.
     *
     * @return int number of settlements released
     */
    public function releaseEligibleSettlements(int $limit = 200): int
    {
        $holdHours = app(SettingService::class)->getStoreReleaseHoldHours();
        $released = 0;

        StoreOrderSettlement::with('store')
            ->where('status', 'settled')
            ->whereNull('released_at')
            ->where('payment_type', '!=', 'CASH')
            ->limit($limit)
            ->get()
            ->each(function (StoreOrderSettlement $settlement) use ($holdHours, &$released) {
                if (! $this->isReleasable($settlement, $holdHours)) {
                    return;
                }

                $amount = round((float) $settlement->net_amount - (float) $settlement->refunded_amount, 2);
                if ($amount <= 0) {
                    $settlement->update(['released_at' => now()]);

                    return;
                }

                $transaction = $this->wallet->add(
                    $settlement->store,
                    $amount,
                    'non_cash_sale',
                    __('Sifariş #:id üzrə xalis gəlir', ['id' => $settlement->order_id]),
                    $settlement->order_id,
                    null,
                    "store-non-cash-sale:{$settlement->id}",
                    [
                        'gross_amount' => (float) $settlement->gross_amount,
                        'commission_amount' => (float) $settlement->commission_amount,
                    ],
                );

                $settlement->update([
                    'released_at' => now(),
                    'wallet_transaction_id' => $settlement->wallet_transaction_id ?: $transaction->id,
                ]);

                $this->notifyStoreOwner(
                    $settlement->store,
                    __('Balansınız artdı'),
                    __('Sifariş #:id üzrə :amount AZN balansınıza köçürüldü.', [
                        'id' => $settlement->order_id,
                        'amount' => number_format($amount, 2),
                    ]),
                    ['type' => 'store_balance_credited', 'order_id' => (string) $settlement->order_id],
                );

                $released++;
            });

        return $released;
    }

    private function isReleasable(StoreOrderSettlement $settlement, int $holdHours): bool
    {
        $delivered = DB::table('order_statuses')
            ->where('order_id', $settlement->order_id)
            ->where('status', OrderStatus::DELIVERED->value)
            ->orderByDesc('id')
            ->value('created_at');

        if (! $delivered) {
            return false;
        }

        // A later cancellation/return status overrides an earlier delivery.
        $latest = DB::table('order_statuses')
            ->where('order_id', $settlement->order_id)
            ->orderByDesc('id')
            ->value('status');

        if ((int) $latest !== OrderStatus::DELIVERED->value) {
            return false;
        }

        return Carbon::parse($delivered)->addHours($holdHours)->isPast();
    }

    private function ensureFulfillment(StoreOrderSettlement $settlement): void
    {
        $hours = app(SettingService::class)->getStoreHandoverHours();

        $fulfillment = StoreOrderFulfillment::firstOrCreate(
            ['store_id' => $settlement->store_id, 'order_id' => $settlement->order_id],
            [
                'status' => 'awaiting',
                'handover_due_at' => now()->addHours($hours),
            ]
        );

        // The merchant only has $hours to hand the goods over, so tell them once,
        // when the obligation is first created. Never let this break the order.
        if ($fulfillment->wasRecentlyCreated) {
            $this->recordSubOrderStatus($settlement->store_id, $settlement->order_id, 'placed');
            $this->notifyStoreOwner(
                $settlement->store,
                __('Yeni sifariş'),
                __('#:order nömrəli sifariş üçün məhsulu :hours saat ərzində Teymur Store-a təhvil verməlisiniz.', [
                    'order' => $settlement->order_id,
                    'hours' => $hours,
                ]),
                ['type' => 'store_new_order', 'order_id' => (string) $settlement->order_id, 'store_id' => (string) $settlement->store_id],
            );
        }
    }

    private function notifyStoreOwner(?Store $store, string $title, string $body, array $data = []): void
    {
        if (! $store?->user_id) {
            return;
        }

        try {
            $this->notifications->add([
                'title' => $title,
                'body' => $body,
                'user_id' => $store->user_id,
                'data' => $data,
            ]);
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    private function commissionPercent(Store $store): float
    {
        return (float) ($store->commission_percent_override
            ?? app(SettingService::class)->getStoreCommissionPercent());
    }

    private function basketItemTotal($item, ?string $pricingType = null): float
    {
        $prices = app(ProductPricingService::class)
            ->prices($item->product, $item->size_id, null, $pricingType);

        return round((float) $prices['final_price'] * (int) $item->quantity, 2);
    }
}
