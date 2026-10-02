<?php

namespace App\Services\Order;

use App\Enums\OrderStatus as OrderStatusEnum;
use App\Services\Notification\OrderStatusNotifier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Order\Entities\Order;
use Modules\Order\Entities\OrderStatus;
use Modules\Payment\Service\PaymentService;

class PendingPaymentExpiryService
{
    public function __construct(private readonly PaymentService $paymentService) {}

    public function expire(int $hours = 3, int $limit = 200): array
    {
        $hours = max(1, $hours);
        $limit = max(1, min($limit, 500));
        $cutoff = now()->subHours($hours);

        $ids = Order::query()
            ->whereNull('paid_at')
            ->where(fn ($query) => $query->where('payment_type', 'CARD')->orWhereNull('payment_type'))
            ->where('created_at', '<=', $cutoff)
            ->whereHas('latestStatus', fn ($query) => $query->where('status', OrderStatusEnum::WAITING_PAYMENT->value))
            ->orderBy('id')
            ->limit($limit)
            ->pluck('id');

        $summary = [
            'selected' => $ids->count(),
            'confirmed_paid' => 0,
            'expired' => 0,
            'skipped' => 0,
            'errors' => [],
            'remaining' => null,
        ];

        foreach ($ids as $id) {
            $order = Order::with('items')->find($id);

            if (! $order) {
                $summary['skipped']++;

                continue;
            }

            try {
                if ($this->paymentService->confirmOrderPaymentIfPaid($order)) {
                    $summary['confirmed_paid']++;

                    continue;
                }
            } catch (\Throwable $e) {
                $summary['skipped']++;
                $summary['errors'][] = [
                    'order_id' => $order->id,
                    'transaction_id' => $order->transaction_id,
                    'error' => $e->getMessage(),
                ];

                Log::warning('Pending payment expiry skipped because Epoint status could not be verified.', [
                    'order_id' => $order->id,
                    'transaction_id' => $order->transaction_id,
                    'error' => $e->getMessage(),
                ]);

                continue;
            }

            try {
                DB::transaction(function () use ($id, $cutoff, &$summary) {
                    $lockedOrder = Order::with('items')->whereKey($id)->lockForUpdate()->first();

                    if (! $lockedOrder || $lockedOrder->paid_at || $lockedOrder->created_at->gt($cutoff)) {
                        $summary['skipped']++;

                        return;
                    }

                    $latestStatus = DB::table('order_statuses')
                        ->where('order_id', $lockedOrder->id)
                        ->orderByDesc('id')
                        ->value('status');

                    if ((int) $latestStatus !== OrderStatusEnum::WAITING_PAYMENT->value) {
                        $summary['skipped']++;

                        return;
                    }

                    foreach ($lockedOrder->items as $item) {
                        DB::table('products')
                            ->where('id', $item->product_id)
                            ->update([
                                'stock_count' => DB::raw('stock_count + '.(int) $item->quantity),
                                'sales_count' => DB::raw('GREATEST(sales_count - '.(int) $item->quantity.', 0)'),
                                'updated_at' => now(),
                            ]);
                    }

                    DB::table('baskets')
                        ->where('user_id', $lockedOrder->user_id)
                        ->where('transaction_id', $lockedOrder->transaction_id)
                        ->where('is_ordered', false)
                        ->update([
                            'transaction_id' => null,
                            'updated_at' => now(),
                        ]);

                    DB::table('used_promo_codes')
                        ->where(fn ($query) => $query
                            ->where('transaction_id', $lockedOrder->transaction_id)
                            ->orWhere('order_id', $lockedOrder->id)
                        )
                        ->update([
                            'transaction_id' => null,
                            'order_id' => null,
                            'updated_at' => now(),
                        ]);

                    // A settlement row is written when the order is placed,
                    // before payment is attempted, so an abandoned basket
                    // leaves one behind for good. Closing it here keeps the
                    // seller's ledger honest and the admin's pending list to
                    // payments that are genuinely still in flight. No money
                    // moves: nothing was ever credited for a pending row.
                    DB::table('store_order_settlements')
                        ->where('order_id', $lockedOrder->id)
                        ->where('status', 'pending')
                        ->update([
                            'status' => 'expired',
                            'reversed_at' => now(),
                            'updated_at' => now(),
                        ]);

                    OrderStatus::create([
                        'order_id' => $lockedOrder->id,
                        'status' => OrderStatusEnum::FAILED,
                    ]);

                    // The customer's basket was just released without them
                    // doing anything; they have to be told why.
                    app(OrderStatusNotifier::class)->notify($lockedOrder, OrderStatusEnum::FAILED);

                    $summary['expired']++;
                });
            } catch (\Throwable $e) {
                $summary['skipped']++;
                $summary['errors'][] = [
                    'order_id' => $order->id,
                    'transaction_id' => $order->transaction_id,
                    'error' => $e->getMessage(),
                ];

                Log::error('Pending payment expiry failed for order.', [
                    'order_id' => $order->id,
                    'transaction_id' => $order->transaction_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $summary['remaining'] = Order::query()
            ->whereNull('paid_at')
            ->where(fn ($query) => $query->where('payment_type', 'CARD')->orWhereNull('payment_type'))
            ->where('created_at', '<=', $cutoff)
            ->whereHas('latestStatus', fn ($query) => $query->where('status', OrderStatusEnum::WAITING_PAYMENT->value))
            ->count();

        return $summary;
    }
}
