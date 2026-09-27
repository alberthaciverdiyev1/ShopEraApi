<?php

namespace Modules\Store\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Payment\Service\EPointService;
use Modules\Store\Http\Entities\Store;
use Modules\Store\Http\Entities\StoreWalletTopup;
use Modules\Store\Services\StoreWalletService;

class StoreWalletPaymentController extends Controller
{
    public function __construct(private StoreWalletService $wallet) {}

    public function start(Request $request)
    {
        $data = $request->validate(['amount' => ['required', 'numeric', 'min:0.1', 'max:100000']]);
        $store = Store::where('user_id', $request->user()->id)->where('status', 'approved')->firstOrFail();
        $transactionId = 'STORE-'.strtoupper(Str::random(20));
        $amount = round((float) $data['amount'], 2);
        $successUrl = route('api.store.wallet-topup-success', ['transaction_id' => $transactionId]);
        $errorUrl = route('api.store.wallet-topup-error', ['transaction_id' => $transactionId]);
        $response = EPointService::typeCard(
            config('app.epoint_private_key'), config('app.epoint_public_key'), $transactionId,
            $amount, "Mağaza balans artımı #{$transactionId}", $successUrl, $errorUrl
        );
        $paymentUrl = $response->redirect_url ?? null;
        if (! $paymentUrl) {
            return responseHelper($response->error ?? 'Payment initialization failed.', 400);
        }

        StoreWalletTopup::create(['store_id' => $store->id, 'transaction_id' => $transactionId, 'amount' => $amount]);

        return responseHelper(__('Payment initialized successfully.'), 200, [
            'payment_url' => $paymentUrl, 'transaction_id' => $transactionId,
        ]);
    }

    public function success(Request $request)
    {
        $transactionId = (string) $request->query('transaction_id');
        $provider = EPointService::checkPayment(
            config('app.epoint_private_key'), config('app.epoint_public_key'), $transactionId
        );
        $success = isset($provider->code) && (string) $provider->code === '000';
        $topup = StoreWalletTopup::where('transaction_id', $transactionId)->firstOrFail();
        $amountPaid = round((float) ($provider->amount ?? 0), 2);
        if (! $success || $amountPaid !== round((float) $topup->amount, 2)) {
            $topup->update(['status' => 'failed']);

            return response('<h2>Ödəniş uğursuz oldu.</h2>', 400)->header('Content-Type', 'text/html');
        }

        DB::transaction(function () use ($topup, $provider, $amountPaid) {
            $locked = StoreWalletTopup::whereKey($topup->id)->lockForUpdate()->firstOrFail();
            if ($locked->status === 'paid') {
                return;
            }
            $this->wallet->add(
                $locked->store, $amountPaid, 'card_top_up', 'Mağaza balansı kartla artırıldı',
                null, $locked->store->user_id, "store-wallet-topup:{$locked->transaction_id}"
            );
            $locked->update([
                'status' => 'paid', 'paid_at' => now(),
                'provider_transaction_id' => $provider->transaction ?? null,
            ]);
        });

        return response('<h2>Mağaza balansı uğurla artırıldı.</h2>', 200)->header('Content-Type', 'text/html');
    }

    public function error(Request $request)
    {
        StoreWalletTopup::where('transaction_id', $request->query('transaction_id'))
            ->where('status', 'waiting')->update(['status' => 'failed']);

        return response('<h2>Ödəniş tamamlanmadı.</h2>', 400)->header('Content-Type', 'text/html');
    }
}
