<?php

namespace Modules\Balance\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Modules\Balance\Http\Entities\Balance;
use App\Enums\BalanceType;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Modules\Balance\Http\Resources\BalanceResource;
use Modules\Payment\Service\EPointService;

class BalanceService
{
    private Balance $model;

    public function __construct(Balance $model)
    {
        $this->model = $model;
    }

        public function deposit($request): JsonResponse
        {
            $validated = $request->validated();

            $validated['user_id'] = $validated['user_id'] ?? auth()->id();

            $type = match ($validated['type'] ?? null) {
                'bonus'      => BalanceType::BONUS->value,
                'withdrawal' => BalanceType::WITHDRAWAL->value,
                default      => BalanceType::DEPOSIT->value,
            };

            return handleTransaction(
                fn() => $this->model->create([
                    'user_id' => $validated['user_id'],
                    'type' => $type ?? BalanceType::DEPOSIT->value,
                    'amount' => (float)$validated['amount'],
                    'note' => $validated['note'] ?? null,
                ])->refresh(),
                'Balance deposited successfully.',
                BalanceResource::class
            );
        }


    public function increaseBalanceUrl(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.1',
        ]);

        $user = auth()->user();
        if (!$user) {
            return responseHelper(__('Unauthorized'), 401);
        }

        $transactionId = 'BLNC-' . uniqid();
        $amount = (float)$validated['amount'];
        $description = "Balans Artımı #{$transactionId}";

        $successUrl = route('api.balance.success', ['transaction_id' => $transactionId]);
        $errorUrl = route('api.balance.error', ['transaction_id' => $transactionId]);

        $paymentResponse = EPointService::typeCard(
            config('app.epoint_private_key'),
            config('app.epoint_public_key'),
            $transactionId,
            $amount,
            $description,
            $successUrl,
            $errorUrl
        );


        if (isset($paymentResponse->error)) {
            return responseHelper($paymentResponse->error, 400);
        }
        $paymentUrl = $paymentResponse->redirect_url ?? null;
        if ($paymentUrl) {
            $balance = $this->model->create([
                'user_id' => $user->id,
                'transaction_order' => $transactionId,
                'type' => BalanceType::WAITING->value,
                'amount' => $amount,
                'note' => "Kartla balans artırımı başlatıldı - {$transactionId}",
            ]);
        }
        return responseHelper($paymentUrl ? 'Payment initialized successfully.' : 'Payment error', $paymentUrl ? 200 : 400, [
            'payment_url' => $paymentUrl,
            'message' => $paymentUrl ? '' : $paymentResponse->message,
            'transaction_order' => $paymentUrl ? $transactionId : null,
        ]);
    }

//    public function increaseBalanceCallback(Request $request): JsonResponse
//    {
//        $transactionId = $request->query('transaction_id');
//
//        $balance = $this->model
//            ->where('transaction_order', $transactionId)
//            ->where('type', BalanceType::WAITING->value)
//            ->first();
//
//        if (!$balance) {
//            return responseHelper(__('Balance record not found.'), 404);
//        }
//
//        $response = EPointService::checkPayment(
//            env('EPOINT_PRIVATE_KEY'),
//            env('EPOINT_PUBLIC_KEY'),
//            $transactionId
//        );
//
//        $success = isset($response->code) && (string)$response->code === '000';
//        $amountPaid = (float)($response->amount ?? 0);
//
//        if ($success) {
//            $balance->update([
//                'type' => BalanceType::DEPOSIT->value,
//                'note' => "Kartla balans artırımı tamamlandı - {$transactionId}",
//            ]);
//
//            return responseHelper(__('Balance deposited successfully.'), 200);
//        }
//
//        $balance->delete();
//
//        return responseHelper(__('Payment failed, balance record deleted.'), 400, [
//            'transaction_order' => $transactionId,
//            'response' => $response,
//        ]);
//    }


    public function increaseBalanceCallback(Request $request)
    {
        $transactionId = $request->query('transaction_id');

        $lang = app()->getLocale();

        $translations = [
            'balance_not_found_title' => __('balance_not_found_title'),
            'balance_not_found_text' => __('balance_not_found_text'),

            'payment_success_title' => __('payment_success_title'),
            'payment_success_text' => __('payment_success_text'),
            'payment_failed_title' => __('payment_failed_title'),
            'payment_failed_text' => __('payment_failed_text'),

            'transaction_code' => __('transaction_code'),
            'amount' => __('amount'),
        ];

        $balance = $this->model
            ->where('transaction_order', $transactionId)
            ->where('type', BalanceType::WAITING->value)
            ->first();

        $style = "
        <style>
            @import url('https://fonts.googleapis.com/css2?family=Lexend:wght@300;400;600&display=swap');
            body {
                font-family: 'Lexend', sans-serif;
                background: linear-gradient(135deg, #fffaf3, #ffe0b2);
                color: #333;
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100vh;
                margin: 0;
            }
            .card {
                background: rgba(255, 255, 255, 0.9);
                border-radius: 22px;
                padding: 70px 90px;
                box-shadow: 0 15px 45px rgba(255, 140, 0, 0.25);
                text-align: center;
                animation: fadeIn 0.8s ease-in-out;
                backdrop-filter: blur(10px);
                max-width: 480px;
                width: 90%;
            }
            .emoji {
                font-size: 70px;
                margin-bottom: 15px;
            }
            h2 {
                font-size: 28px;
                margin-bottom: 15px;
                letter-spacing: 0.3px;
            }
            p {
                font-size: 17px;
                color: #555;
                margin-bottom: 8px;
            }
            .amount {
                color: #ff6f00;
                font-weight: 600;
                font-size: 18px;
            }
            .code {
                margin-top: 12px;
                padding: 10px 16px;
                background: rgba(255, 152, 0, 0.1);
                border-radius: 10px;
                color: #a45a00;
                display: inline-block;
                font-size: 15px;
            }
            @keyframes fadeIn {
                from {opacity: 0; transform: translateY(20px);}
                to {opacity: 1; transform: translateY(0);}
            }
            @media (max-width: 600px) {
                .card { padding: 50px 30px; }
                h2 { font-size: 22px; }
                p { font-size: 15px; }
                .emoji { font-size: 60px; }
            }
        </style>
    ";

        if (!$balance) {
            $title = $translations['balance_not_found_title'];
            $text = $translations['balance_not_found_text'];

            return response("
            <html lang='{$lang}'>
                <head><meta charset='UTF-8'><title>{$title}</title>{$style}</head>
                <body>
                    <div class='card'>
                        <div class='emoji'>⚠️</div>
                        <h2 style='color:#e65100'>{$title}</h2>
                        <p>{$text}</p>
                    </div>
                </body>
            </html>
        ", 404)->header('Content-Type', 'text/html');
        }

        $response = EPointService::checkPayment(
            config('app.epoint_private_key'),
            config('app.epoint_public_key'),
            $transactionId
        );

        $success = isset($response->code) && (string)$response->code === '000';
        $amountPaid = (float)($response->amount ?? 0);

        if ($success) {
            $balance->update([
                'type' => BalanceType::DEPOSIT->value,
                'note' => "Kartla balans artırımı tamamlandı - {$transactionId}",
            ]);

            $title = $translations['payment_success_title'];
            $text = $translations['payment_success_text'];
            $code = $translations['transaction_code'];
            $amount = $translations['amount'];

            return response("
            <html lang='{$lang}'>
                <head><meta charset='UTF-8'><title>{$title}</title>{$style}</head>
                <body>
                    <div class='card'>
                        <div class='emoji' style='color:#ff9800'>🎉</div>
                        <h2 style='color:#e65100'>{$title}</h2>
                        <p>{$text}</p>
                        <p class='code'>{$code}: {$transactionId}</p>
                        <p><b>{$amount}:</b> <span class='amount'>{$amountPaid} ₼</span></p>
                    </div>
                </body>
            </html>
        ", 200)->header('Content-Type', 'text/html');
        }

        $balance->delete();

        $title = $translations['payment_failed_title'];
        $text = $translations['payment_failed_text'];
        $code = $translations['transaction_code'];

        return response("
        <html lang='{$lang}'>
            <head><meta charset='UTF-8'><title>{$title}</title>{$style}</head>
            <body>
                <div class='card'>
                    <div class='emoji' style='color:#ff7043'>❌</div>
                    <h2 style='color:#d84315'>{$title}</h2>
                    <p>{$text}</p>
                    <p class='code'>{$code}: {$transactionId}</p>
                </div>
            </body>
        </html>
    ", 400)->header('Content-Type', 'text/html');
    }

    public function callbackDeposit($userId, $amount, $note, $type = null): JsonResponse
    {
        return handleTransaction(
            function () use ($userId, $amount, $note, $type) {

                $finalType = $type
                    ? (BalanceType::tryFrom(strtolower($type)) ?? BalanceType::DEPOSIT)
                    : BalanceType::DEPOSIT;

                return $this->model->create([
                    'user_id' => $userId,
                    'type' => $finalType->value,
                    'amount' => (float)$amount,
                    'note' => $note,
                ])->refresh();
            },
            'Transaction completed successfully.',
            BalanceResource::class,
            200
        );
    }

    public function error(Request $request)
    {
        $transactionId = $request->query('transaction_id');

        $lang = app()->getLocale();

        $balance = $this->model
            ->where('transaction_order', $transactionId)
            ->where('type', BalanceType::WAITING->value)
            ->first();

        if (!$balance) {
            return responseHelper(__('balance_not_found'), 404);
        }

        $balance->delete();

        $title = __('payment_failed_title');
        $text = __('payment_failed_text');
        $code = __('transaction_code');

        $html = "
                <!DOCTYPE html>
                <html lang='{$lang}'>
                <head>
                    <meta charset='UTF-8'>
                    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                    <title>{$title}</title>
                    <style>
                        @import url('https://fonts.googleapis.com/css2?family=Lexend:wght@300;400;600&display=swap');
                        :root {
                            --orange: #ff7b00;
                            --dark-orange: #e65100;
                            --error: #d84315;
                            --light-bg: #fffaf3;
                        }
                        body {
                            margin: 0;
                            height: 100vh;
                            background: linear-gradient(145deg, var(--light-bg), #ffe0b2);
                            font-family: 'Lexend', sans-serif;
                            display: flex;
                            justify-content: center;
                            align-items: center;
                            color: #333;
                        }
                        .card {
                            background: rgba(255,255,255,0.9);
                            backdrop-filter: blur(12px);
                            border-radius: 24px;
                            box-shadow: 0 15px 40px rgba(255, 138, 0, 0.2);
                            padding: 70px 90px;
                            text-align: center;
                            max-width: 480px;
                            width: 90%;
                            animation: fadeInUp 0.8s ease-out;
                        }
                        .emoji {
                            font-size: 75px;
                            margin-bottom: 20px;
                            color: var(--error);
                            animation: popIn 0.5s ease-in-out;
                        }
                        h2 {
                            color: var(--error);
                            font-size: 28px;
                            font-weight: 600;
                            margin-bottom: 14px;
                            letter-spacing: 0.3px;
                        }
                        p {
                            font-size: 17px;
                            color: #555;
                            margin-bottom: 10px;
                            line-height: 1.5;
                        }
                        .transaction {
                            margin-top: 20px;
                            padding: 10px 16px;
                            background: rgba(255, 112, 0, 0.08);
                            border-radius: 10px;
                            font-size: 15px;
                            color: #a45a00;
                            display: inline-block;
                        }
                        @keyframes fadeInUp {
                            from { opacity: 0; transform: translateY(20px); }
                            to { opacity: 1; transform: translateY(0); }
                        }
                        @keyframes popIn {
                            0% { transform: scale(0.8); opacity: 0; }
                            100% { transform: scale(1); opacity: 1; }
                        }
                        @media (max-width: 600px) {
                            .card { padding: 50px 30px; }
                            h2 { font-size: 22px; }
                            p { font-size: 15px; }
                            .emoji { font-size: 60px; }
                        }
                    </style>
                </head>
                <body>
                    <div class='card'>
                        <div class='emoji'>❌</div>
                        <h2>{$title}</h2>
                        <p>{$text}</p>
                        <div class='transaction'>{$code}: {$transactionId}</div>
                    </div>
                </body>
                </html>
                ";

        return response($html, 400)->header('Content-Type', 'text/html');
    }

    public function withdraw(int $user_id, float $amount, string $note = null): JsonResponse
    {
        return handleTransaction(
            fn() => $this->model->create([
                'user_id' => $user_id,
                'type' => BalanceType::WITHDRAWAL->value,
                'amount' => $amount,
                'note' => $note,
            ])->refresh(),
            'Balance withdrawn successfully.',
            BalanceResource::class,
            200
        );
    }


    public function getBalance(int $userId = null): JsonResponse
    {
        $userId = $userId ?? auth()->id();

        $totalBalance = $this->model
            ->where('user_id', $userId)
            ->whereNotIn('type', ['waiting'])
            ->sum(DB::raw("
                CASE
                    WHEN type IN ('deposit','refund','bonus','referral') THEN amount
                    ELSE -amount
                END
            "));
        return responseHelper(__('Balance retrieved successfully.'), 200, [
            'user_id' => $userId,
            'balance' => (float)$totalBalance,
        ]);

    }

    public function getBalanceHistory(int $userId = null, bool $is_admin = false): JsonResponse
    {
        $userId = $userId ?? auth()->id();

        $query = $this->model
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc');

        if (!$is_admin) {
            $query->whereNotIn('type', ['waiting']);
        }

        $history = $query->get();
        return responseHelper(__('Balance history retrieved successfully.'), 200, BalanceResource::collection($history));
    }
}
