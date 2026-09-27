<?php

namespace Modules\Store\Services;

use App\Services\Notification\AdminNotifier;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Notification\Services\NotificationService;
use Modules\Setting\Services\SettingService;
use Modules\Store\Http\Entities\Store;
use Modules\Store\Http\Entities\StoreWithdrawal;

/**
 * The withdrawal lifecycle: pending -> approved -> paid, or rejected at either
 * step. Money is only deducted from the balance at "paid"; until then an open
 * request simply reserves the amount (see StoreBalanceService::reserved) so the
 * same money cannot be requested twice.
 */
class StoreWithdrawalService
{
    public function __construct(
        private StoreWalletService $wallet,
        private StoreBalanceService $balances,
        private MarketplaceAuditService $audit,
        private NotificationService $notifications,
    ) {}

    public function request(Store $store, float $amount, ?string $note = null): StoreWithdrawal
    {
        $amount = round($amount, 2);
        $minimum = app(SettingService::class)->getStoreMinWithdrawalAmount();

        if ($amount <= 0) {
            throw ValidationException::withMessages(['amount' => __('Məbləğ sıfırdan böyük olmalıdır.')]);
        }

        if ($minimum > 0 && $amount < $minimum) {
            throw ValidationException::withMessages([
                'amount' => __('Minimum çıxarış məbləği :amount AZN-dir.', ['amount' => number_format($minimum, 2)]),
            ]);
        }

        return DB::transaction(function () use ($store, $amount, $note) {
            // Lock the store row so two concurrent requests cannot both pass the
            // available-balance check against the same money.
            $locked = Store::whereKey($store->id)->lockForUpdate()->firstOrFail();
            $available = $this->balances->withdrawable($locked);

            if ($amount > $available) {
                throw ValidationException::withMessages([
                    'amount' => __('Çıxarıla bilən balans :amount AZN-dir.', ['amount' => number_format($available, 2)]),
                ]);
            }

            $withdrawal = StoreWithdrawal::create([
                'store_id' => $locked->id,
                'amount' => $amount,
                'status' => 'pending',
                'note' => $note,
            ]);

            $this->audit->log('withdrawal.requested', $withdrawal, ['amount' => $amount]);
            $this->notify($locked, __('Çıxarış müraciəti qəbul edildi'),
                __(':amount AZN üçün müraciətiniz yoxlanılır.', ['amount' => number_format($amount, 2)]),
                'store_withdrawal_pending', $withdrawal->id);

            // Money is reserved the moment this is created, so staff should not
            // have to discover the request by opening the queue.
            app(AdminNotifier::class)->notify(
                __('Yeni çıxarış müraciəti'),
                __('":store" mağazası :amount AZN çıxarış istəyir.', [
                    'store' => $locked->name,
                    'amount' => number_format($amount, 2),
                ]),
                [
                    'type' => 'admin_withdrawal_requested',
                    'withdrawal_id' => (string) $withdrawal->id,
                    'store_id' => (string) $locked->id,
                ],
            );

            return $withdrawal;
        });
    }

    public function approve(StoreWithdrawal $withdrawal, int $adminId): StoreWithdrawal
    {
        $this->assertStatus($withdrawal, ['pending']);

        $withdrawal->update([
            'status' => 'approved',
            'reviewed_by' => $adminId,
            'reviewed_at' => now(),
        ]);

        $this->audit->log('withdrawal.approved', $withdrawal);
        $this->notify($withdrawal->store, __('Çıxarış müraciəti təsdiqləndi'),
            __(':amount AZN ödəniş üçün hazırdır.', ['amount' => number_format((float) $withdrawal->amount, 2)]),
            'store_withdrawal_approved', $withdrawal->id);

        return $withdrawal->fresh();
    }

    /**
     * The only step that moves money. Guarded by an idempotency key so a double
     * click cannot debit the merchant twice.
     */
    public function markPaid(StoreWithdrawal $withdrawal, int $adminId, ?string $adminNote = null): StoreWithdrawal
    {
        $this->assertStatus($withdrawal, ['pending', 'approved']);

        return DB::transaction(function () use ($withdrawal, $adminId, $adminNote) {
            $locked = StoreWithdrawal::whereKey($withdrawal->id)->lockForUpdate()->firstOrFail();

            if ($locked->status === 'paid') {
                return $locked;
            }

            $transaction = $this->wallet->add(
                $locked->store,
                -(float) $locked->amount,
                'withdrawal_paid',
                __('Çıxarış #:id ödənildi', ['id' => $locked->id]),
                null,
                $adminId,
                "store-withdrawal:{$locked->id}",
                ['withdrawal_id' => $locked->id],
                false,
                $adminNote,
            );

            $locked->update([
                'status' => 'paid',
                'paid_by' => $adminId,
                'paid_at' => now(),
                'wallet_transaction_id' => $transaction->id,
            ]);

            $this->audit->log('withdrawal.paid', $locked, ['amount' => (float) $locked->amount]);
            $this->notify($locked->store, __('Çıxarış ödənildi'),
                __(':amount AZN balansınızdan çıxarıldı.', ['amount' => number_format((float) $locked->amount, 2)]),
                'store_withdrawal_paid', $locked->id);

            return $locked->fresh();
        });
    }

    public function reject(StoreWithdrawal $withdrawal, int $adminId, string $reason): StoreWithdrawal
    {
        $this->assertStatus($withdrawal, ['pending', 'approved']);

        $withdrawal->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
            'reviewed_by' => $adminId,
            'reviewed_at' => now(),
        ]);

        $this->audit->log('withdrawal.rejected', $withdrawal, ['reason' => $reason]);
        $this->notify($withdrawal->store, __('Çıxarış müraciəti rədd edildi'),
            __('Səbəb: :reason', ['reason' => $reason]),
            'store_withdrawal_rejected', $withdrawal->id);

        return $withdrawal->fresh();
    }

    /** Merchant-side cancel while the request is still untouched. */
    public function cancel(StoreWithdrawal $withdrawal): StoreWithdrawal
    {
        $this->assertStatus($withdrawal, ['pending']);

        $withdrawal->update(['status' => 'cancelled']);
        $this->audit->log('withdrawal.cancelled', $withdrawal);

        return $withdrawal->fresh();
    }

    private function assertStatus(StoreWithdrawal $withdrawal, array $allowed): void
    {
        if (! in_array($withdrawal->status, $allowed, true)) {
            throw ValidationException::withMessages([
                'status' => __('Bu müraciət üzərində həmin əməliyyat mümkün deyil.'),
            ]);
        }
    }

    private function notify(?Store $store, string $title, string $body, string $type, int $withdrawalId): void
    {
        if (! $store?->user_id) {
            return;
        }

        try {
            $this->notifications->add([
                'title' => $title,
                'body' => $body,
                'user_id' => $store->user_id,
                'data' => [
                    'type' => $type,
                    'withdrawal_id' => (string) $withdrawalId,
                    'store_id' => (string) $store->id,
                ],
            ]);
        } catch (\Throwable $exception) {
            report($exception);
        }
    }
}
