<?php

namespace Modules\Payment\Service;

use App\Enums\OrderStatus as OrderStatusEnum;
use App\Services\Notification\OrderStatusNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Balance\Services\BalanceService;
use Modules\Order\Entities\Order;
use Modules\Order\Entities\OrderStatus;
use Modules\Order\Services\OrderService;
use Modules\PromoCode\Services\PromoCodeService;
use Modules\User\Entities\Basket;

class PaymentService
{
    private BalanceService $balanceService;

    private OrderService $orderService;

    private PromoCodeService $promoCodeService;

    public function __construct(BalanceService $balanceService, OrderService $orderService, PromoCodeService $promoCodeService)
    {
        $this->balanceService = $balanceService;
        $this->orderService = $orderService;
        $this->promoCodeService = $promoCodeService;
    }

    //    public function start(Request $request)
    //    {
    //        $lang = app()->getLocale();
    //        $orderId = $request->get('order_id');
    //        $price = number_format($request->get('price', 0), 2);
    //        $assetsUrl = asset('assets');
    //
    //        if (!$orderId) {
    //            return response("Sifariş məlumatı tapılmadı (Missing order_id).", 400);
    //        }
    //
    //        $html = <<<HTML
    //                <!DOCTYPE html>
    //                <html lang="{$lang}">
    //                <head>
    //                    <meta charset="UTF-8">
    //                    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    //                    <title>Ödəniş Üsulu - Snaker</title>
    //                    <style>
    //                        body { background: #f8f9fa; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; font-family: 'Inter', sans-serif; }
    //                        .payment-card { background: #fff; padding: 2.5rem; border-radius: 24px; box-shadow: 0 20px 40px rgba(0,0,0,0.08); width: 100%; max-width: 400px; text-align: center; border: 1px solid #edf2f7; }
    //                        .payment-logo { max-width: 140px; margin-bottom: 2rem; }
    //                        .price-badge { background: #fff5f5; color: #e53e3e; font-size: 1.75rem; font-weight: 800; padding: 12px 24px; border-radius: 16px; display: inline-block; margin-bottom: 1.5rem; border: 1px dashed #feb2b2; }
    //                        .payment-title { font-size: 1.4rem; font-weight: 700; color: #1a202c; margin-bottom: 0.5rem; }
    //                        .payment-subtitle { color: #718096; font-size: 0.95rem; margin-bottom: 2.5rem; line-height: 1.5; }
    //                        .method-btn {
    //                            display: flex; align-items: center; justify-content: center; gap: 12px;
    //                            width: 100%; padding: 16px; margin-bottom: 14px; border: 2px solid #edf2f7;
    //                            border-radius: 16px; cursor: pointer; transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    //                            background: #fff; font-weight: 600; font-size: 1.05rem; color: #2d3748;
    //                        }
    //                        .method-btn:hover { border-color: #4299e1; background: #ebf8ff; transform: translateY(-2px); }
    //                        .method-btn.selected { border-color: #4299e1; background: #ebf8ff; box-shadow: 0 4px 12px rgba(66, 153, 225, 0.15); }
    //                        .method-btn img { height: 24px; pointer-events: none; }
    //                        .btn-pay {
    //                            background: #000; color: #fff; border: none; width: 100%;
    //                            padding: 18px; border-radius: 16px; font-weight: 700; font-size: 1.15rem;
    //                            margin-top: 20px; cursor: pointer; display: none; transition: all 0.3s;
    //                        }
    //                        .btn-pay:hover { background: #2d3748; transform: scale(1.02); }
    //                        .loading { opacity: 0.6; pointer-events: none; }
    //                    </style>
    //                </head>
    //                <body>
    //                    <div class="payment-card">
    //                        <img src="{$assetsUrl}/images/logo.png" alt="Snaker" class="payment-logo">
    //                        <div class="price-badge">{$price} AZN</div>
    //                        <h2 class="payment-title">Ödəniş üsulunu seçin</h2>
    //                        <p class="payment-subtitle">Təhlükəsiz ödəniş üçün aşağıdakı üsullardan birini seçin.</p>
    //
    //                        <div id="payment-options">
    //                            <button class="method-btn" onclick="selectMethod('apple_google_pay', this)">
    //                                <img src="https://upload.wikimedia.org/wikipedia/commons/b/b0/Apple_Pay_logo.svg" alt="Apple Pay">
    //                                <span style="color:#cbd5e0; margin: 0 5px;">|</span>
    //                                <img src="https://upload.wikimedia.org/wikipedia/commons/f/f2/Google_Pay_Logo.svg" alt="Google Pay">
    //                            </button>
    //
    //                            <button class="method-btn" onclick="selectMethod('card', this)">
    //                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
    //                                    <rect x="2" y="5" width="20" height="14" rx="2" ry="2"></rect>
    //                                    <line x1="2" y1="10" x2="22" y2="10"></line>
    //                                </svg>
    //                                Bank Kartı
    //                            </button>
    //                        </div>
    //
    //                        <button id="submit-btn" class="btn-pay" onclick="processPayment()">Təsdiqlə və Ödə</button>
    //                    </div>
    //
    //                    <script>
    //                        let selectedMethod = null;
    //                        const orderId = "{$orderId}";
    //                        const price = "{$request->get('price')}";
    //
    //                        function selectMethod(method, element) {
    //                            selectedMethod = method;
    //                            document.querySelectorAll('.method-btn').forEach(btn => btn.classList.remove('selected'));
    //                            element.classList.add('selected');
    //                            document.getElementById('submit-btn').style.display = 'block';
    //                        }
    //
    //                        async function processPayment() {
    //                            if (!selectedMethod) return;
    //
    //                            const btn = document.getElementById('submit-btn');
    //                            btn.innerText = "Yönləndirilir...";
    //                            btn.classList.add('loading');
    //
    //                            try {
    //                                const response = await fetch('/api/payment/create', {
    //                                    method: 'POST',
    //                                    headers: {
    //                                        'Content-Type': 'application/json',
    //                                        'Accept': 'application/json',
    //                                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
    //                                    },
    //                                    body: JSON.stringify({
    //                                        order_id: orderId,
    //                                        method: selectedMethod,
    //                                        price: price
    //                                    })
    //                                });
    //
    //                                const data = await response.json();
    //
    //                                if (data.redirect_url) {
    //                                    window.location.href = data.redirect_url;
    //                                } else {
    //                                    alert('Xəta: ' + (data.message || 'Ödəniş prosesi başlanmadı'));
    //                                    btn.innerText = "Təsdiqlə və Ödə";
    //                                    btn.classList.remove('loading');
    //                                }
    //                            } catch (error) {
    //                                console.error('Fetch Error:', error);
    //                                alert('Sistem xətası baş verdi.');
    //                                btn.classList.remove('loading');
    //                                btn.innerText = "Təsdiqlə və Ödə";
    //                            }
    //                        }
    //                    </script>
    //                </body>
    //                </html>
    //                HTML;
    //        return response($html, 200)->header('Content-Type', 'text/html');
    //    }

    public function start(Request $request)
    {
        $lang = $request->query('lang', app()->getLocale());
        $orderId = $request->query('order_id');
        $order = Order::where('transaction_id', $orderId)->first();
        $rawPrice = $order ? (float) $order->total_price + (float) $order->shipping_price : 0;
        $price = number_format($rawPrice, 2);
        $currency = (string) config('app.paypal_currency');
        $assetsUrl = asset('assets');

        if (! $orderId || ! $order) {
            return response('Sifariş məlumatı tapılmadı.', 400);
        }

        if (! $this->canAcceptPayment($order)) {
            return response('Bu sifariş üçün ödəniş müddəti bitib.', 410);
        }

        $csrfToken = csrf_token();
        $createUrl = route('api.payment.create');

        $html = <<<HTML
                <!DOCTYPE html>
                <html lang="{$lang}">
                <head>
                    <meta charset="UTF-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                    <title>Ödəniş - Snaker</title>
                    <style>
                        body { background: #f8f9fa; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; font-family: sans-serif; }
                        .payment-card { background: #fff; padding: 2.5rem; border-radius: 24px; box-shadow: 0 20px 40px rgba(0,0,0,0.08); width: 90%; max-width: 400px; text-align: center; }
                        .price-badge { background: #fff5f5; color: #e53e3e; font-size: 1.75rem; font-weight: 800; padding: 12px 24px; border-radius: 16px; display: inline-block; margin-bottom: 1.5rem; border: 1px dashed #feb2b2; }
                        .btn-pay { background: #0070ba; color: #fff; border: none; width: 100%; padding: 18px; border-radius: 16px; font-weight: 700; font-size: 1.05rem; cursor: pointer; }
                        .btn-pay:disabled { opacity: 0.6; cursor: wait; }
                        .hint { color: #718096; font-size: 0.9rem; margin-top: 1.25rem; }
                    </style>
                </head>
                <body>
                    <div class="payment-card">
                        <img src="{$assetsUrl}/images/logo.png" style="max-width:140px; margin-bottom:2rem;" onerror="this.style.display='none'">
                        <div class="price-badge">{$price} {$currency}</div>
                        <button id="submit-btn" class="btn-pay" onclick="processPayment()">PayPal ilə ödə</button>
                        <p class="hint">Təhlükəsiz ödəniş üçün PayPal səhifəsinə yönləndiriləcəksiniz.</p>
                    </div>

                    <script>
                        async function processPayment() {
                            const btn = document.getElementById('submit-btn');
                            btn.disabled = true;
                            btn.innerText = "Yönləndirilir...";

                            try {
                                const res = await fetch("{$createUrl}", {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': "{$csrfToken}" },
                                    body: JSON.stringify({ order_id: "{$orderId}", price: "{$rawPrice}" })
                                });
                                const data = await res.json();
                                if (data.redirect_url) {
                                    window.location.href = data.redirect_url;
                                } else {
                                    alert(data.message || 'Ödəniş linki alınmadı.');
                                    btn.disabled = false;
                                    btn.innerText = "PayPal ilə ödə";
                                }
                            } catch (e) {
                                alert('Xəta baş verdi.');
                                btn.disabled = false;
                                btn.innerText = "PayPal ilə ödə";
                            }
                        }
                    </script>
                </body>
                </html>
                HTML;

        return response($html, 200)->header('Content-Type', 'text/html');
    }

    public function createPayment(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'required',
        ]);

        $orderId = $validated['order_id'];
        $order = Order::where('transaction_id', $orderId)->first();

        if (! $order) {
            return response()->json(['message' => 'Sifariş məlumatı tapılmadı'], 404);
        }

        if (! $this->canAcceptPayment($order)) {
            return response()->json(['message' => 'Bu sifariş üçün ödəniş müddəti bitib.'], 410);
        }

        $amount = $this->expectedAmount($order);
        $currency = (string) config('app.paypal_currency');

        try {
            $paypal = app(PayPalService::class);

            $paypalOrder = $paypal->createOrder(
                $orderId,
                $amount,
                $currency,
                "Payment for order #{$orderId}",
                route('api.payment.success', ['transaction_id' => $orderId]),
                route('api.payment.error', ['transaction_id' => $orderId])
            );

            $redirectUrl = $paypal->approveUrl($paypalOrder);

            if (! $redirectUrl) {
                return response()->json(['message' => 'Ödəniş linki alınmadı'], 422);
            }

            $order->update(['paypal_order_id' => $paypalOrder->id ?? null]);

            return response()->json(['redirect_url' => $redirectUrl]);

        } catch (\Throwable $e) {
            Log::error('PayPal create order failed.', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function success(Request $request)
    {
        Log::info('Payment success callback received.', [
            'transaction_id' => $request->query('transaction_id'),
            'paypal_order_id' => $request->query('token'),
        ]);

        $lang = app()->getLocale();
        $currency = (string) config('app.paypal_currency');

        $translations = [
            'payment_success_title' => __('payment_success_title'),
            'payment_success_text' => __('payment_success_text'),
            'payment_failed_title' => __('payment_failed_title'),
            'payment_failed_text' => __('payment_failed_text'),
            'transaction_code' => __('transaction_code'),
            'amount' => __('amount'),
            'order_not_found_title' => __('order_not_found_title'),
            'order_not_found_text' => __('order_not_found_text'),
        ];

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

        $order = $this->resolveOrderFromRequest($request);
        $transactionId = $order?->transaction_id ?? $request->query('transaction_id');

        if (! $order) {
            $title = $translations['order_not_found_title'];
            $text = $translations['order_not_found_text'];

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

        if ($order->paid_at) {
            $captured = true;
            $amountPaid = $this->expectedAmount($order);
        } else {
            try {
                [$captured, $amountPaid] = $this->capturePayPalOrder($order, $request->query('token'));
            } catch (\Throwable $e) {
                Log::error('PayPal capture failed.', [
                    'order_id' => $order->id,
                    'transaction_id' => $order->transaction_id,
                    'error' => $e->getMessage(),
                ]);

                $captured = false;
                $amountPaid = 0;
            }
        }

        if ($captured) {
            $this->completePaidOrder($order, (float) $amountPaid, $request->query('token'));

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
                            <p><b>{$amount}:</b> <span class='amount'>{$amountPaid} {$currency}</span></p>
                        </div>
                    </body>
                </html>
                ", 200)->header('Content-Type', 'text/html');
        }

        $this->recordPaymentFailure($order);

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
        ", 403)->header('Content-Type', 'text/html');
    }

    public function error(Request $request)
    {
        Log::warning('Payment error callback received.', [
            'transaction_id' => $request->query('transaction_id'),
        ]);

        $transactionId = $request->query('transaction_id');
        $lang = app()->getLocale();

        $translations = [
            'payment_failed_title' => __('payment_failed_title'),
            'payment_failed_text' => __('payment_failed_text'),
            'order_not_found_title' => __('order_not_found_title'),
            'order_not_found_text' => __('order_not_found_text'),
            'transaction_code' => __('transaction_code'),
        ];

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

        $order = Order::where('transaction_id', $transactionId)->first();

        if (! $order) {
            $title = $translations['order_not_found_title'];
            $text = $translations['order_not_found_text'];

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

        if (! $order->fresh()->paid_at) {
            $this->recordPaymentFailure($order);

            Basket::where('user_id', $order->user_id)
                ->where('transaction_id', $transactionId)
                ->update(['is_ordered' => false]);
        }

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
    ", 500)->header('Content-Type', 'text/html');
    }

    public function status(Request $request)
    {
        $transactionId = $request->query('transaction_id');

        if (! $transactionId) {
            return response()->json(['paid' => false, 'message' => 'Transaction id is required.'], 422);
        }

        $order = Order::where('transaction_id', $transactionId)->first();

        if (! $order) {
            return response()->json(['paid' => false, 'message' => 'Order not found.'], 404);
        }

        if ($order->paid_at) {
            return response()->json(['paid' => true]);
        }

        try {
            if ($this->confirmOrderPaymentIfPaid($order)) {
                return response()->json(['paid' => true]);
            }
        } catch (\Throwable $e) {
            Log::warning('PayPal payment status polling failed.', [
                'order_id' => $order->id,
                'transaction_id' => $order->transaction_id,
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json(['paid' => false]);
    }

    public function result(Request $request)
    {
        Log::info('PayPal webhook received.', [
            'event_type' => $request->input('event_type'),
            'event_id' => $request->input('id'),
            'ip' => $request->ip(),
        ]);

        $paypal = app(PayPalService::class);

        if (! $paypal->verifyWebhook($request->headers->all(), $request->all())) {
            Log::warning('PayPal webhook signature validation failed.');

            return responseHelper(__('Invalid callback signature.'), 403);
        }

        $eventType = (string) $request->input('event_type');
        $resource = (array) $request->input('resource', []);

        $handled = [
            'PAYMENT.CAPTURE.COMPLETED',
            'PAYMENT.CAPTURE.DENIED',
            'PAYMENT.CAPTURE.REFUNDED',
        ];

        if (! in_array($eventType, $handled, true)) {
            // Acknowledge unhandled events so PayPal stops retrying.
            return responseHelper(__('Event ignored'), 200, ['event_type' => $eventType]);
        }

        $order = $this->resolveOrderFromCaptureResource($resource);

        if (! $order) {
            return responseHelper(__('Order not found'), 403);
        }

        if ($order->paid_at) {
            return responseHelper(__('Payment successful'), 200, ['order_id' => $order->id]);
        }

        if ($eventType !== 'PAYMENT.CAPTURE.COMPLETED') {
            $this->recordPaymentFailure($order);

            return responseHelper(__('Payment failed'), 200, ['order_id' => $order->id]);
        }

        $amountPaid = (float) ($resource['amount']['value'] ?? 0);
        $expectedAmount = $this->expectedAmount($order);

        if (abs($amountPaid - $expectedAmount) >= 0.01) {
            Log::warning('PayPal webhook amount mismatch.', [
                'order_id' => $order->id,
                'amount_paid' => $amountPaid,
                'expected_amount' => $expectedAmount,
            ]);

            return responseHelper(__('Payment is not confirmed yet'), 409, [
                'order_id' => $order->id,
                'message' => 'Paid amount does not match the order total.',
            ]);
        }

        $paypalOrderId = $resource['supplementary_data']['related_ids']['order_id'] ?? $order->paypal_order_id;
        $captureId = $resource['id'] ?? null;

        if (! $this->completePaidOrder($order, $amountPaid, $paypalOrderId, $captureId)) {
            return responseHelper(__('Payment is not confirmed yet'), 409, [
                'order_id' => $order->id,
                'message' => 'Order is no longer waiting for payment.',
            ]);
        }

        return responseHelper(__('Payment successful'), 200, [
            'order_id' => $order->id,
            'status' => OrderStatusEnum::PLACED,
            'amount_paid' => $amountPaid,
        ]);
    }

    public function confirmOrderPaymentIfPaid(Order $order): bool
    {
        if ($order->paid_at) {
            return true;
        }

        $paypalOrderId = $order->paypal_order_id;

        if (! $paypalOrderId) {
            return false;
        }

        $paypal = app(PayPalService::class);
        $paypalOrder = $paypal->getOrder($paypalOrderId);
        $status = $paypalOrder->status ?? null;

        if ($status === 'COMPLETED') {
            $capture = $this->firstCapture($paypalOrder);

            return $this->completePaidOrder(
                $order,
                (float) ($capture->amount->value ?? 0),
                $paypalOrderId,
                $capture->id ?? null
            );
        }

        // Approved but not yet captured (e.g. the buyer never returned to success()).
        if ($status === 'APPROVED') {
            [$captured, $amountPaid, $captureId] = $this->capturePayPalOrder($order, $paypalOrderId);

            if ($captured) {
                return $this->completePaidOrder($order, (float) $amountPaid, $paypalOrderId, $captureId);
            }
        }

        return false;
    }

    private function completePaidOrder(Order $order, float $amountPaid, ?string $paypalOrderId = null, ?string $captureId = null): bool
    {
        return DB::transaction(function () use ($order, $amountPaid, $paypalOrderId, $captureId) {
            $lockedOrder = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($lockedOrder->paid_at) {
                return true;
            }

            if (! $this->canAcceptPayment($lockedOrder)) {
                Log::warning('Paid PayPal callback received for an order that is no longer waiting for payment.', [
                    'order_id' => $lockedOrder->id,
                    'transaction_id' => $lockedOrder->transaction_id,
                ]);

                return false;
            }

            $userId = $lockedOrder->user_id;
            $transactionId = $lockedOrder->transaction_id;

            $lockedOrder->update([
                'paid_at' => now(),
                'paypal_order_id' => $paypalOrderId ?? $lockedOrder->paypal_order_id,
                'paypal_capture_id' => $captureId ?? $lockedOrder->paypal_capture_id,
            ]);

            $this->recordOrderStatus($lockedOrder, OrderStatusEnum::PLACED);

            Basket::where('user_id', $userId)
                ->where('transaction_id', $transactionId)
                ->where('is_ordered', false)
                ->update(['is_ordered' => true, 'transaction_id' => $transactionId]);

            $this->ensureBalanceOperationSucceeded(
                $this->balanceService->callbackDeposit(
                    $userId,
                    $amountPaid,
                    "Added amount to balance for order: $lockedOrder->id"
                )
            );

            $this->ensureBalanceOperationSucceeded(
                $this->balanceService->withdraw(
                    $userId,
                    $amountPaid,
                    "Removed amount to balance for order: $lockedOrder->id"
                )
            );

            $promoCodeId = DB::table('used_promo_codes')
                ->where('transaction_id', $transactionId)
                ->value('promo_code_id');

            if ($promoCodeId) {
                $this->promoCodeService->decrementPromoCodeCount($promoCodeId);

                DB::table('used_promo_codes')
                    ->where('transaction_id', $transactionId)
                    ->update(['transaction_id' => null]);
            }

            return true;
        });
    }

    /**
     * Capture an approved PayPal order and validate the paid amount.
     *
     * @return array{0: bool, 1: float, 2: ?string}
     */
    private function capturePayPalOrder(Order $order, ?string $paypalOrderId): array
    {
        if (! $paypalOrderId) {
            return [false, 0.0, null];
        }

        $paypal = app(PayPalService::class);
        $capture = $paypal->captureOrder($paypalOrderId);

        if (($capture->status ?? null) !== 'COMPLETED') {
            Log::warning('PayPal capture did not complete.', [
                'order_id' => $order->id,
                'status' => $capture->status ?? null,
            ]);

            return [false, 0.0, null];
        }

        $firstCapture = $this->firstCapture($capture);
        $amountPaid = (float) ($firstCapture->amount->value ?? 0);
        $captureId = $firstCapture->id ?? null;

        if (abs($amountPaid - $this->expectedAmount($order)) >= 0.01) {
            Log::warning('PayPal capture amount mismatch.', [
                'order_id' => $order->id,
                'amount_paid' => $amountPaid,
                'expected_amount' => $this->expectedAmount($order),
            ]);

            return [false, $amountPaid, $captureId];
        }

        $order->update([
            'paypal_order_id' => $paypalOrderId,
            'paypal_capture_id' => $captureId,
        ]);

        return [true, $amountPaid, $captureId];
    }

    /**
     * Pull the first capture object out of a PayPal order/capture payload.
     */
    private function firstCapture(object $payload): object
    {
        $unit = $payload->purchase_units[0] ?? null;

        return $unit->payments->captures[0] ?? (object) [];
    }

    private function expectedAmount(Order $order): float
    {
        return round((float) $order->total_price + (float) $order->shipping_price, 2);
    }

    /**
     * Resolve the local order from a success/error redirect
     * (transaction_id query param or PayPal's appended token).
     */
    private function resolveOrderFromRequest(Request $request): ?Order
    {
        $transactionId = $request->query('transaction_id');

        if ($transactionId) {
            $order = Order::where('transaction_id', $transactionId)->first();

            if ($order) {
                return $order;
            }
        }

        $paypalOrderId = $request->query('token');

        if ($paypalOrderId) {
            return Order::where('paypal_order_id', $paypalOrderId)->first();
        }

        return null;
    }

    /**
     * Resolve the local order from a PayPal webhook capture resource.
     */
    private function resolveOrderFromCaptureResource(array $resource): ?Order
    {
        $orderId = $resource['custom_id'] ?? ($resource['invoice_id'] ?? null);

        if ($orderId) {
            $order = Order::where('transaction_id', $orderId)->first();

            if ($order) {
                return $order;
            }
        }

        $paypalOrderId = $resource['supplementary_data']['related_ids']['order_id'] ?? null;

        if ($paypalOrderId) {
            return Order::where('paypal_order_id', $paypalOrderId)->first();
        }

        return null;
    }

    private function ensureBalanceOperationSucceeded($response): void
    {
        $payload = $response->getData(true);

        if (! ($payload['success'] ?? false)) {
            throw new \RuntimeException('Failed to write payment balance history.');
        }
    }

    private function recordOrderStatus(Order $order, OrderStatusEnum $status): void
    {
        $latestStatus = OrderStatus::where('order_id', $order->id)
            ->latest('id')
            ->value('status');

        if ($latestStatus === null || OrderStatusEnum::resolve($latestStatus) !== $status) {
            OrderStatus::create([
                'order_id' => $order->id,
                'status' => $status,
            ]);

            // Payment outcomes reach the customer from here — a successful card
            // payment and a failed one both used to pass in silence.
            app(OrderStatusNotifier::class)->notify($order, $status);
        }
    }

    private function recordPaymentFailure(Order $order): void
    {
        if (! $order->fresh()->paid_at) {
            $this->recordOrderStatus($order, OrderStatusEnum::FAILED);
        }
    }

    private function canAcceptPayment(Order $order): bool
    {
        if ($order->paid_at) {
            return true;
        }

        $latestStatus = DB::table('order_statuses')
            ->where('order_id', $order->id)
            ->orderByDesc('id')
            ->value('status');

        return (int) $latestStatus === OrderStatusEnum::WAITING_PAYMENT->value;
    }
}
