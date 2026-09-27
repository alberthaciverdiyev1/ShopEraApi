<?php

namespace Modules\Payment\Service;

use App\Services\Notification\OrderStatusNotifier;
use App\Enums\OrderStatus as OrderStatusEnum;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Balance\Services\BalanceService;
use Modules\Order\Http\Entities\Order;
use Modules\Order\Http\Entities\OrderStatus;
use Modules\Order\Services\OrderService;
use Modules\PromoCode\Services\PromoCodeService;
use Modules\User\Http\Entities\Basket;
use Modules\Store\Services\MerchantOrderService;

class PaymentService
{
    private BalanceService $balanceService;
    private OrderService $orderService;
    private PromoCodeService $promoCodeService;
    private MerchantOrderService $merchantOrders;

    function __construct(BalanceService $balanceService, OrderService $orderService, PromoCodeService $promoCodeService, MerchantOrderService $merchantOrders)
    {
        $this->balanceService = $balanceService;
        $this->orderService = $orderService;
        $this->promoCodeService = $promoCodeService;
        $this->merchantOrders = $merchantOrders;
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
//                    <title>Ödəniş Üsulu - Teymur Store</title>
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
//                        <img src="{$assetsUrl}/images/logo.png" alt="Teymur Store" class="payment-logo">
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
        $rawPrice = $order ? (float)$order->total_price + (float)$order->shipping_price : 0;
        $price = number_format($rawPrice, 2);
        $assetsUrl = asset('assets');

        if (!$orderId || !$order) return response("Sifariş məlumatı tapılmadı.", 400);

        if (!$this->canAcceptPayment($order)) {
            return response("Bu sifariş üçün ödəniş müddəti bitib.", 410);
        }

        $csrfToken = csrf_token();
        $createUrl = route('api.payment.create');
        $successUrl = route('api.payment.success', ['transaction_id' => $orderId]);
        $statusUrl = route('api.payment.status', ['transaction_id' => $orderId]);

        $html = <<<HTML
                <!DOCTYPE html>
                <html lang="{$lang}">
                <head>
                    <meta charset="UTF-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                    <title>Ödəniş - Teymur Store</title>
                    <style>
                        body { background: #f8f9fa; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; font-family: sans-serif; }
                        .payment-card { background: #fff; padding: 2.5rem; border-radius: 24px; box-shadow: 0 20px 40px rgba(0,0,0,0.08); width: 90%; max-width: 400px; text-align: center; }
                        .price-badge { background: #fff5f5; color: #e53e3e; font-size: 1.75rem; font-weight: 800; padding: 12px 24px; border-radius: 16px; display: inline-block; margin-bottom: 1.5rem; border: 1px dashed #feb2b2; }
                        .method-btn { display: flex; align-items: center; justify-content: center; gap: 12px; width: 100%; padding: 16px; margin-bottom: 14px; border: 2px solid #edf2f7; border-radius: 16px; cursor: pointer; transition: all 0.2s; background: #fff; font-weight: 600; }
                        .method-btn.selected { border-color: #4299e1; background: #ebf8ff; }
                        .btn-pay { background: #000; color: #fff; border: none; width: 100%; padding: 18px; border-radius: 16px; font-weight: 700; display: none; cursor: pointer; }
                        .loading-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255,255,255,0.9); z-index: 1000; align-items: center; justify-content: center; flex-direction: column; }
                        .widget-modal { display: none; position: fixed; inset: 0; background: #fff; z-index: 900; }
                        .widget-frame { width: 100%; height: 100%; border: 0; }
                        .widget-close { position: absolute; top: 12px; right: 12px; z-index: 901; border: 0; border-radius: 999px; width: 42px; height: 42px; background: rgba(255,255,255,0.92); font-size: 20px; cursor: pointer; }
                    </style>
                </head>
                <body>
                    <div id="loading" class="loading-overlay">
                        <div style="font-weight: bold;">Ödəniş yoxlanılır...</div>
                    </div>

                    <div class="payment-card" id="ui">
                        <img src="{$assetsUrl}/images/logo.png" style="max-width:140px; margin-bottom:2rem;" onerror="this.style.display='none'">
                        <div class="price-badge">{$price} AZN</div>
                        <div id="payment-options">
                            <button class="method-btn" onclick="selectMethod('apple_google_pay', this)">Apple/Google Pay</button>
                            <button class="method-btn" onclick="selectMethod('card', this)">💳 Bank Kartı</button>
                        </div>
                        <button id="submit-btn" class="btn-pay" onclick="processPayment()">Ödənişi Tamamla</button>
                    </div>

                    <div id="widget-modal" class="widget-modal">
                        <button type="button" class="widget-close" onclick="closeWidget()">X</button>
                        <iframe id="widget-frame" class="widget-frame" src="about:blank" allow="payment *" allowpaymentrequest></iframe>
                    </div>

                    <script>
                        let selectedMethod = null;
                        let finalizing = false;
                        let widgetOrigin = null;
                        let statusPollTimer = null;
                        let statusPollStartedAt = null;
                        let statusPollInFlight = false;
                        const successUrl = "{$successUrl}";
                        const statusUrl = "{$statusUrl}";
                        const statusPollTimeoutMs = 10 * 60 * 1000;

                        window.addEventListener('message', function(e) {
                            if (!isTrustedEpointOrigin(e.origin)) return;

                            if (e.data && (e.data.status === 'success' || e.data === 'success')) {
                                finalizePayment();
                            } else if (e.data && (e.data.status === 'error' || e.data.status === 'cancel')) {
                                closeWidget();
                            }
                        });

                        function selectMethod(method, element) {
                            selectedMethod = method;
                            document.querySelectorAll('.method-btn').forEach(b => b.classList.remove('selected'));
                            element.classList.add('selected');
                            document.getElementById('submit-btn').style.display = 'block';
                        }

                        async function processPayment() {
                            const btn = document.getElementById('submit-btn');
                            btn.disabled = true;
                            btn.innerText = "Yönləndirilir...";

                            try {
                                const res = await fetch("{$createUrl}", {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': "{$csrfToken}" },
                                    body: JSON.stringify({ order_id: "{$orderId}", method: selectedMethod, price: "{$rawPrice}" })
                                });
                                const data = await res.json();
                                if (data.redirect_url) {
                                    if (selectedMethod === 'apple_google_pay') {
                                        openWidget(data.redirect_url);
                                        btn.disabled = false;
                                        btn.innerText = "Ödənişi Tamamla";
                                    } else {
                                        window.location.href = data.redirect_url;
                                    }
                                } else {
                                    alert(data.message || 'Ödəniş linki alınmadı.');
                                    btn.disabled = false;
                                    btn.innerText = "Ödənişi Tamamla";
                                }
                            } catch (e) {
                                alert('Xəta baş verdi.');
                                btn.disabled = false;
                                btn.innerText = "Ödənişi Tamamla";
                            }
                        }

                        async function finalizePayment() {
                            if (finalizing) return;
                            finalizing = true;
                            stopStatusPolling();
                            document.getElementById('loading').style.display = 'flex';
                            document.getElementById('ui').style.opacity = '0.3';

                            try {
                                await fetch(successUrl, { method: 'GET' });
                            } catch (e) {}

                            window.location.href = successUrl;
                        }

                        function openWidget(url) {
                            const widgetUrl = new URL(url);
                            widgetOrigin = widgetUrl.origin;
                            document.getElementById('widget-frame').src = widgetUrl.toString();
                            document.getElementById('widget-modal').style.display = 'block';
                            startStatusPolling();
                        }

                        function closeWidget() {
                            stopStatusPolling();
                            document.getElementById('widget-modal').style.display = 'none';
                            document.getElementById('widget-frame').src = 'about:blank';
                            widgetOrigin = null;
                        }

                        function startStatusPolling() {
                            stopStatusPolling();
                            statusPollStartedAt = Date.now();
                            checkPaymentStatus();
                            statusPollTimer = window.setInterval(checkPaymentStatus, 3000);
                        }

                        function stopStatusPolling() {
                            if (statusPollTimer !== null) {
                                window.clearInterval(statusPollTimer);
                                statusPollTimer = null;
                            }

                            statusPollStartedAt = null;
                            statusPollInFlight = false;
                        }

                        async function checkPaymentStatus() {
                            if (statusPollInFlight) return;
                            if (statusPollStartedAt !== null && Date.now() - statusPollStartedAt > statusPollTimeoutMs) {
                                stopStatusPolling();
                                return;
                            }

                            statusPollInFlight = true;

                            try {
                                const response = await fetch(statusUrl, {
                                    method: 'GET',
                                    headers: { 'Accept': 'application/json' }
                                });

                                if (!response.ok) return;

                                const data = await response.json();
                                if (data.paid === true) finalizePayment();
                            } catch (e) {
                            } finally {
                                statusPollInFlight = false;
                            }
                        }

                        function isTrustedEpointOrigin(origin) {
                            return widgetOrigin !== null && origin === widgetOrigin;
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
            'method'   => 'required|in:apple_google_pay,card',
        ]);

        $orderId = $validated['order_id'];
        $method  = $validated['method'];
        $order = Order::where('transaction_id', $orderId)->first();

        if (!$order) {
            return response()->json(['message' => 'Sifariş məlumatı tapılmadı'], 404);
        }

        if (!$this->canAcceptPayment($order)) {
            return response()->json(['message' => 'Bu sifariş üçün ödəniş müddəti bitib.'], 410);
        }

        $amount = round((float)$order->total_price + (float)$order->shipping_price, 2);

        try {
            if ($method === 'apple_google_pay') {
                $paymentResponse = EPointService::widgetPay(
                    config('app.epoint_private_key'),
                    config('app.epoint_public_key'),
                    $amount,
                    $orderId,
                    "Payment for order #{$orderId}"
                );
            } else {
                $paymentResponse = EPointService::saveCardAndPayment(
                    config('app.epoint_private_key'),
                    config('app.epoint_public_key'),
                    $orderId,
                    $amount,
                    "Payment for order #{$orderId}",
                    route('api.payment.success', ['transaction_id' => $orderId]),
                    route('api.payment.error', ['transaction_id' => $orderId])
                );
            }

            $redirectUrl = $paymentResponse->redirect_url ?? ($paymentResponse->widget_url ?? null);

            if (!$redirectUrl) {
                return response()->json(['message' => 'Ödəniş linki alınmadı'], 422);
            }

            return response()->json(['redirect_url' => $redirectUrl]);

        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }


    public function success(Request $request)
    {
        Log::info('Payment success callback received.', [
            'transaction_id' => $request->query('transaction_id'),
        ]);

        $transactionId = $request->query('transaction_id');
        $lang = app()->getLocale();

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

        $order = Order::where('transaction_id', $transactionId)->first();

        if (!$order) {
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

        $response = EPointService::checkPayment(
            config('app.epoint_private_key'),
            config('app.epoint_public_key'),
            $order->transaction_id
        );
        Log::info('Epoint success response received.', [
            'transaction_id' => $order->transaction_id,
            'status' => $response->status ?? null,
            'code' => $response->code ?? null,
            'message' => $response->message ?? null,
            'amount' => $response->amount ?? null,
        ]);

        $success = isset($response->code) && (string)$response->code === '000';
        $amountPaid = $response->amount ?? 0;
        $expectedAmount = round((float)$order->total_price + (float)$order->shipping_price, 2);
        $hasCorrectAmount = abs((float)$amountPaid - $expectedAmount) < 0.01;

        if ($success && $hasCorrectAmount) {
            $this->completePaidOrder($order, $response);

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

        if (!$order) {
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

        if (!$order->fresh()->paid_at) {
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

        if (!$transactionId) {
            return response()->json(['paid' => false, 'message' => 'Transaction id is required.'], 422);
        }

        $order = Order::where('transaction_id', $transactionId)->first();

        if (!$order) {
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
            Log::warning('Epoint payment status polling failed.', [
                'order_id' => $order->id,
                'transaction_id' => $order->transaction_id,
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json(['paid' => false]);
    }


    public function result(Request $request)
    {
        Log::info('Epoint result callback received.', [
            'has_data' => $request->filled('data'),
            'has_signature' => $request->filled('signature'),
            'ip' => $request->ip(),
        ]);

        $transactionId = $request->query('transaction_id');

        if ($request->filled('data') || $request->filled('signature')) {
            $data = $request->input('data');
            $signature = $request->input('signature');

            if (!is_string($data) || !is_string($signature)) {
                return responseHelper(__('Invalid callback payload.'), 403);
            }

            if (!EPointService::verifyCallback(
                config('app.epoint_private_key'),
                config('app.epoint_public_key'),
                $data,
                $signature
            )) {
                Log::warning('Epoint result callback signature validation failed.');

                return responseHelper(__('Invalid callback signature.'), 403);
            }

            $payload = EPointService::decodeCallbackData($data);
            $transactionId = $payload?->order_id;
        }

        if (!$transactionId) {
            return responseHelper(__('Order id not found in callback.'), 403);
        }

        $order = Order::where('transaction_id', $transactionId)->first();
        if (!$order) {
            return responseHelper(__('Order not found'), 403);
        }

        $response = EPointService::checkPayment(
            config('app.epoint_private_key'),
            config('app.epoint_public_key'),
            $order->transaction_id
        );

        $success = isset($response->code) && (string)$response->code === '000';
        $amountPaid = (float)($response->amount ?? 0);
        $expectedAmount = round((float)$order->total_price + (float)$order->shipping_price, 2);
        $hasCorrectAmount = abs($amountPaid - $expectedAmount) < 0.01;

        if ($success && $hasCorrectAmount) {
            if (!$this->completePaidOrder($order, $response)) {
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

        Log::warning('Epoint payment callback did not pass status validation.', [
            'order_id' => $order->id,
            'transaction_id' => $order->transaction_id,
            'status_code' => $response->code ?? null,
            'amount_paid' => $amountPaid,
            'expected_amount' => $expectedAmount,
        ]);

        return responseHelper(__('Payment is not confirmed yet'), 409, [
            'order_id' => $order->id,
            'response' => $response,
        ]);
    }

    public function confirmOrderPaymentIfPaid(Order $order): bool
    {
        if ($order->paid_at) {
            return true;
        }

        $response = EPointService::checkPayment(
            config('app.epoint_private_key'),
            config('app.epoint_public_key'),
            $order->transaction_id
        );

        $success = isset($response->code) && (string)$response->code === '000';
        $amountPaid = (float)($response->amount ?? 0);
        $expectedAmount = round((float)$order->total_price + (float)$order->shipping_price, 2);
        $hasCorrectAmount = abs($amountPaid - $expectedAmount) < 0.01;

        if ($success && $hasCorrectAmount) {
            return $this->completePaidOrder($order, $response);
        }

        if ($success && !$hasCorrectAmount) {
            Log::warning('Epoint polling found a payment with an unexpected amount.', [
                'order_id' => $order->id,
                'transaction_id' => $order->transaction_id,
                'amount_paid' => $amountPaid,
                'expected_amount' => $expectedAmount,
            ]);
        }

        return false;
    }

    private function completePaidOrder(Order $order, object $response): bool
    {
        return DB::transaction(function () use ($order, $response) {
            $lockedOrder = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($lockedOrder->paid_at) {
                return true;
            }

            if (!$this->canAcceptPayment($lockedOrder)) {
                Log::warning('Paid Epoint callback received for an order that is no longer waiting for payment.', [
                    'order_id' => $lockedOrder->id,
                    'transaction_id' => $lockedOrder->transaction_id,
                ]);

                return false;
            }

            $userId = $lockedOrder->user_id;
            $transactionId = $lockedOrder->transaction_id;
            $amountPaid = (float)($response->amount ?? 0);
            $paymentUpdate = [
                'paid_at' => now(),
                'e_point_transaction' => $response->transaction ?? null,
            ];

            if (!empty($response->card_id)) {
                $paymentUpdate['card_id'] = $response->card_id;

                $exists = DB::table('user_saved_cards')
                    ->where('user_id', $userId)
                    ->where('card_id', $response->card_id)
                    ->exists();

                if (!$exists) {
                    DB::table('user_saved_cards')->insert([
                        'user_id' => $userId,
                        'card_id' => $response->card_id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            $lockedOrder->update($paymentUpdate);
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

            $this->merchantOrders->settlePaidOrder($lockedOrder->fresh());

            return true;
        });
    }

    private function ensureBalanceOperationSucceeded($response): void
    {
        $payload = $response->getData(true);

        if (!($payload['success'] ?? false)) {
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
        if (!$order->fresh()->paid_at) {
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
