<?php

namespace Modules\Store\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Notification\Services\NotificationService;
use Modules\Setting\Services\SettingService;
use Modules\Store\Http\Entities\Store;
use Modules\Store\Http\Entities\StoreWalletTransaction;

class StoreWalletService
{
    public function add(
        Store $store,
        float $amount,
        string $type,
        ?string $note = null,
        ?int $orderId = null,
        ?int $createdBy = null,
        ?string $idempotencyKey = null,
        ?array $meta = null,
        bool $enforceNegativeLimit = false,
        ?string $adminNote = null,
    ): StoreWalletTransaction {
        if (round($amount, 2) === 0.0) {
            throw ValidationException::withMessages(['amount' => 'Amount must not be zero.']);
        }

        return DB::transaction(function () use ($store, $amount, $type, $note, $orderId, $createdBy, $idempotencyKey, $meta, $enforceNegativeLimit, $adminNote) {
            if ($idempotencyKey) {
                $existing = StoreWalletTransaction::where('idempotency_key', $idempotencyKey)->first();
                if ($existing) {
                    return $existing;
                }
            }

            $lockedStore = Store::whereKey($store->id)->lockForUpdate()->firstOrFail();
            $before = (float) $lockedStore->balance;
            $after = round($before + $amount, 2);
            $limit = (float) ($lockedStore->negative_balance_limit_override
                ?? app(SettingService::class)->getStoreNegativeBalanceLimit());

            if ($enforceNegativeLimit && $after < -$limit) {
                throw ValidationException::withMessages([
                    'store_balance' => 'Mağazanın mənfi balans limiti sifarişi qəbul etməyə imkan vermir.',
                ]);
            }

            $lockedStore->balance = $after;

            $wasActive = (bool) $lockedStore->is_active;

            if ($lockedStore->status === 'approved') {
                if ($after <= -$limit) {
                    $lockedStore->is_active = false;
                    $lockedStore->deactivated_reason = 'negative_balance_limit';
                } elseif ($lockedStore->deactivated_reason === 'negative_balance_limit') {
                    $lockedStore->is_active = true;
                    $lockedStore->deactivated_reason = null;
                }
            }

            $lockedStore->save();

            // Having the shop pulled off the marketplace is the single most
            // consequential thing that happens to a merchant, and it used to
            // happen without a word.
            if ($wasActive !== (bool) $lockedStore->is_active) {
                $this->notifyActivationChange($lockedStore, $limit);
            }

            return StoreWalletTransaction::create([
                'store_id' => $lockedStore->id,
                'order_id' => $orderId,
                'created_by' => $createdBy,
                'type' => $type,
                'amount' => round($amount, 2),
                'balance_before' => $before,
                'balance_after' => $after,
                'idempotency_key' => $idempotencyKey,
                'note' => $note,
                'admin_note' => $adminNote,
                'meta' => $meta,
            ]);
        });
    }

    private function notifyActivationChange(Store $store, float $limit): void
    {
        if (! $store->user_id) {
            return;
        }

        try {
            $deactivated = ! $store->is_active;

            app(NotificationService::class)->add([
                'title' => $deactivated
                    ? __('Mağazanız satışdan çıxarıldı')
                    : __('Mağazanız yenidən aktivdir'),
                'body' => $deactivated
                    ? __('Balansınız :limit AZN mənfi limitini keçdi. Balansı artırdıqdan sonra mağazanız avtomatik açılacaq.', [
                        'limit' => number_format($limit, 2),
                    ])
                    : __('Balansınız limitə qayıtdı, məhsullarınız yenidən satışdadır.'),
                'user_id' => $store->user_id,
                'data' => [
                    'type' => $deactivated ? 'store_deactivated' : 'store_reactivated',
                    'store_id' => (string) $store->id,
                    'balance' => (string) $store->balance,
                ],
            ]);
        } catch (\Throwable $exception) {
            report($exception);
        }
    }
}
