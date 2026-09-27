<?php

namespace Modules\Payment\Service;


use GuzzleHttp\Client;
use Illuminate\Support\Facades\Http;

class EPointService
{

    public $epoint_transaction;
    // the order id you give when processing, in order to find out for what the payment is made for
    public $order_id;
    // the id returned by Epoint after user save his/her hard in the system
    public $card_uid;
    // private key given by Epoint
    public $private_key;
    // public key given by Epoint
    public $public_key;
    // the amount of money
    public $amount;
    // currency, possible values AZN
    public $currency = 'AZN';
    // language, possible values az, en, ru
    public $language = 'az';
    // description for the payment
    public $description;
    // when operation is successful the url to redirect for
    public $success_redirect_url;
    // when operation failed the url to redirect for
    public $error_redirect_url;

    // these 2 variables are ues by callback webhook
    public $signature;
    public $data;

    public $languages = ['az', 'en', 'ru','tr'];
    // http client
    public $client;
    // http response
    public $response;

    public function __construct($data = [])
    {
        if (!empty($data)) {
            foreach ($data as $key => $value) {
                $this->$key = $value;
            }
        }
    }

    public function instantiateForSendingRequest()
    {
        $this->client = new Client();

        if (!empty($_SESSION['lang']) && in_array($_SESSION['lang'], $this->languages)) {
            $this->language = $_SESSION['lang'];
        } else {
            $this->language = 'az';
        }

        return $this;
    }

//    public function sign($json_data)
//    {
//        $this->data = base64_encode(json_encode($json_data));
//
//        $this->signature = base64_encode(sha1("{$this->private_key}{$this->data}{$this->private_key}", true));
//    }

    public function sign($json_data)
    {
        $jsonString = json_encode($json_data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $this->data = base64_encode($jsonString);

        $rawString = $this->private_key . $this->data . $this->private_key;

        $this->signature = base64_encode(sha1($rawString, true));
    }

    public function createSignatureByData()
    {
        return base64_encode(sha1("{$this->private_key}{$this->data}{$this->private_key}", true));
    }

    public function isSignatureValid()
    {
        return is_string($this->signature)
            && hash_equals($this->createSignatureByData(), $this->signature);
    }

    public function getDataAsJson()
    {
        return base64_decode($this->data);
    }

    public function getDataAsObject()
    {
        return json_decode(base64_decode($this->data));
    }

    public function generatePaymentUrlWithTypingCard()
    {
        $json_data = [
            'public_key' => $this->public_key,
            'language' => $this->language,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'order_id' => $this->order_id,
            'description' => $this->description,
            'success_redirect_url' => $this->success_redirect_url,
            'error_redirect_url' => $this->error_redirect_url,
        ];

        $this->sign($json_data);

        $response = $this->client->request('POST', 'https://epoint.az/api/1/request', [
            'form_params' => [
                'data' => $this->data,
                'signature' => $this->signature,
            ]
        ]);

        $this->response = json_decode($response->getBody());

        return $this;
    }

    public function getStatus()
    {
        $json_data = [
            'public_key' => $this->public_key,
        ];

        if ($this->order_id) {
            $json_data['order_id'] = $this->order_id;
        }

        if ($this->epoint_transaction) {
            $json_data['transaction'] = $this->epoint_transaction;
        }

        $this->sign($json_data);

        $response = $this->client->request('POST', 'https://epoint.az/api/1/get-status', [
            'form_params' => [
                'data' => $this->data,
                'signature' => $this->signature,
            ]
        ]);

        $this->response = json_decode($response->getBody());

        return $this;
    }

    public function registerCardForPayment()
    {
        $json_data = [
            'public_key' => $this->public_key,
            'language' => $this->language,
            'refund' => 0,
            'description' => $this->description,
            'success_redirect_url' => $this->success_redirect_url,
            'error_redirect_url' => $this->error_redirect_url,
        ];

        $this->sign($json_data);

        $response = $this->client->request('POST', "https://epoint.az/api/1/card-registration", [
            'form_params' => [
                'data' => $this->data,
                'signature' => $this->signature,
            ]
        ]);

        $this->response = json_decode($response->getBody());

        return $this;
    }

    public function registerAndPayment()
    {
        $json_data = [
            'public_key' => $this->public_key,
            'language' => $this->language,
            'order_id' => $this->order_id,
            'amount' => $this->amount,
            "currency" => $this->currency,
            'description' => $this->description,
            'success_redirect_url' => $this->success_redirect_url,
            'error_redirect_url' => $this->error_redirect_url,
        ];

        $this->sign($json_data);

        $response = $this->client->request('POST', "https://epoint.az/api/1/card-registration-with-pay", [
            'form_params' => [
                'data' => $this->data,
                'signature' => $this->signature,
            ]
        ]);

        $this->response = json_decode($response->getBody());

        return $this;
    }

    public function registerCardForRefund()
    {
        $json_data = [
            'public_key' => $this->public_key,
            'language' => $this->language,
            'refund' => 1,
            'description' => $this->description,
            'success_redirect_url' => $this->success_redirect_url,
            'error_redirect_url' => $this->error_redirect_url,
        ];

        $this->sign($json_data);

        $response = $this->client->request('POST', "https://epoint.az/api/1/card-registration", [
            'form_params' => [
                'data' => $this->data,
                'signature' => $this->signature,
            ]
        ]);

        $this->response = json_decode($response->getBody());

        return $this;
    }

    public function payWithSavedCard()
    {
        $json_data = [
            'public_key' => $this->public_key,
            'language' => $this->language,
            'card_uid' => $this->card_uid,
            'order_id' => $this->order_id,
            'amount' => $this->amount,
            'description' => $this->description,
            'currency' => $this->currency,
        ];

        $this->sign($json_data);

        $response = $this->client->request('POST', 'https://epoint.az/api/1/execute-pay', [
            'form_params' => [
                'data' => $this->data,
                'signature' => $this->signature,
            ]
        ]);

        $this->response = json_decode($response->getBody());

        return $this;
    }

    public function cancelPayment()
    {
        $json_data = [
            'public_key' => $this->public_key,
            'language' => $this->language,
            'transaction' => $this->epoint_transaction,
            'currency' => $this->currency,
        ];

        if ($this->amount) {
            $json_data['amount'] = $this->amount;
        }

        $this->sign($json_data);

        $response = $this->client->request('POST', 'https://epoint.az/api/1/reverse', [
            'form_params' => [
                'data' => $this->data,
                'signature' => $this->signature,
            ]
        ]);

        $this->response = json_decode($response->getBody());

        return $this;
    }

    public function refundPayment()
    {
        $json_data = [
            'public_key' => $this->public_key,
            'language' => $this->language,
            'card_id' => $this->card_uid,
            'order_id' => $this->order_id,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'description' => $this->description,
        ];

        $this->sign($json_data);

        $response = $this->client->request('POST', 'https://epoint.az/api/1/refund-request', [
            'form_params' => [
                'data' => $this->data,
                'signature' => $this->signature,
            ]
        ]);

        $this->response = json_decode($response->getBody());

        return $this;
    }

    public function mobilePayment()
    {
        $json_data = [
            'public_key' => $this->public_key,
            'amount' => $this->amount,
            'order_id' => $this->order_id,
            'description' => $this->description,
        ];

        $this->sign($json_data);

        $response = $this->client->request('POST', 'https://epoint.az/api/1/token/widget', [
            'form_params' => [
                'data' => $this->data,
                'signature' => $this->signature,
            ]
        ]);

//        $response = Http::post("https://epoint.az/api/1/token/widget", [
//            'data' => $this->data,
//            'signature' => $this->signature
//        ]);
        $this->response = json_decode($response->getBody());

        return $this;
    }

    // ===================================================================
    //  Apple Pay & Google Pay (Web) — Widget
    // ===================================================================

    /**
     * Widget URL-i əldə et (Apple Pay / Google Pay üçün)
     * POST https://epoint.az/api/1/token/widget
     */
    public function getWidgetUrl()
    {
        $json_data = [
            'public_key' => $this->public_key,
            'amount' => $this->amount,
            'order_id' => $this->order_id,
            'description' => $this->description,
        ];

        $this->sign($json_data);

        $response = $this->client->request('POST', 'https://epoint.az/api/1/token/widget', [
            'form_params' => [
                'data' => $this->data,
                'signature' => $this->signature,
            ]
        ]);

        $this->response = json_decode($response->getBody());

        return $this;
    }

    /**
     * Widget iframe HTML-i qaytar
     */
    public function renderWidgetIframe($width = 600, $height = 400): string
    {
        if (!empty($this->response->widget_url)) {
            return sprintf(
                '<iframe src="%s" width="%d" height="%d" frameborder="0" allowpaymentrequest></iframe>',
                htmlspecialchars($this->response->widget_url, ENT_QUOTES, 'UTF-8'),
                $width,
                $height
            );
        }

        $error = htmlspecialchars($this->response->message ?? 'Widget creation failed', ENT_QUOTES, 'UTF-8');
        return "<p style='color:red;'>$error</p>";
    }

    /**
     * Widget nəticəsini dinləmək üçün JavaScript
     */
//    public static function widgetResultScript(): string
//    {
//        return <<<'JS'
//        <script>
//        window.addEventListener('message', function(event) {
//            console.log('Epoint widget result:', event.data);
//            window.dispatchEvent(new CustomEvent('epoint-payment', { detail: event.data }));
//        });
//        </script>
//        JS;
//    }

    public static function instantiate($private_key, $public_key)
    {
        $epoint = new self([
            "private_key" => $private_key,
            "public_key" => $public_key
        ]);
        $epoint->instantiateForSendingRequest();
        return $epoint;
    }

    public static function checkPayment($private_key, $public_key, $uid, $epoint_transaction = false)
    {
        $epoint = static::instantiate($private_key, $public_key);

        if ($epoint_transaction) {
            $epoint->epoint_transaction = $uid;
        } else {
            $epoint->order_id = $uid;
        }

        $epoint->getStatus();

        return $epoint->response;
    }

    public static function typeCard($private_key, $public_key, $order_id, $amount, $description, $success_redirect_url = null, $error_redirect_url = null)
    {
        $epoint = static::instantiate($private_key, $public_key);

        $epoint->order_id = $order_id;
        $epoint->amount = $amount;
        $epoint->description = $description;

        if ($success_redirect_url) {
            $epoint->success_redirect_url = $success_redirect_url;
        }

        if ($error_redirect_url) {
            $epoint->error_redirect_url = $error_redirect_url;
        }

        $epoint->generatePaymentUrlWithTypingCard();

        return $epoint->response;
    }

    public static function saveCardAndPayment($private_key, $public_key, $order_id, $amount, $description, $success_redirect_url = null, $error_redirect_url = null)
    {

        $epoint = static::instantiate($private_key, $public_key);

        $epoint->order_id = $order_id;
        $epoint->amount = $amount;
        $epoint->description = $description;

        if ($success_redirect_url) {
            $epoint->success_redirect_url = $success_redirect_url;
        }

        if ($error_redirect_url) {
            $epoint->error_redirect_url = $error_redirect_url;
        }

        $epoint->registerAndPayment();

        return $epoint->response;
    }

    public static function saveCardForPayment($private_key, $public_key, $description, $success_redirect_url = null, $error_redirect_url = null)
    {
        $epoint = static::instantiate($private_key, $public_key);

        $epoint->description = $description;

        if ($success_redirect_url) {
            $epoint->success_redirect_url = $success_redirect_url;
        }

        if ($error_redirect_url) {
            $epoint->error_redirect_url = $error_redirect_url;
        }

        $epoint->registerCardForPayment();

        return $epoint->response;
    }


    public static function saveCardForRefund($private_key, $public_key, $description, $success_redirect_url = null, $error_redirect_url = null)
    {
        $epoint = static::instantiate($private_key, $public_key);

        $epoint->description = $description;

        if ($success_redirect_url) {
            $epoint->success_redirect_url = $success_redirect_url;
        }

        if ($error_redirect_url) {
            $epoint->error_redirect_url = $error_redirect_url;
        }

        $epoint->registerCardForRefund();

        return $epoint->response;
    }

    public static function payWithSaved($private_key, $public_key, $card_uid, $order_id, $amount, $description)
    {
        $epoint = static::instantiate($private_key, $public_key);

        $epoint->card_uid = $card_uid;
        $epoint->order_id = $order_id;
        $epoint->amount = $amount;
        $epoint->description = $description;

        $epoint->payWithSavedCard();

        return $epoint->response;
    }

    /*
     * if $amount is not given all amount per transaction will return to the card
     * */
    public static function cancel($private_key, $public_key, $epoint_transaction, $amount = null)
    {
        $epoint = static::instantiate($private_key, $public_key);

        $epoint->epoint_transaction = $epoint_transaction;

        if ($amount) {
            $epoint->amount = $amount;
        }

        $epoint->cancelPayment();

        return $epoint->response;
    }

    public static function refund($private_key, $public_key, $card_uid, $order_id, $amount, $description)
    {
        $epoint = static::instantiate($private_key, $public_key);

        $epoint->card_uid = $card_uid;
        $epoint->order_id = $order_id;
        $epoint->amount = $amount;
        $epoint->description = $description;

        $epoint->refundPayment();

        return $epoint->response;
    }

    public static function mobilePay ($private_key, $public_key,$amount,$order_id, $description)
    {
        $epoint = static::instantiate($private_key, $public_key);

        $epoint->order_id = $order_id;
        $epoint->amount =$amount;
        $epoint->description = $description;

        $epoint->mobilePayment();

        return $epoint->response;
    }

    // ===================================================================
    //  Apple Pay & Google Pay static shortcuts
    // ===================================================================

    /**
     * Apple Pay / Google Pay widget URL-i al
     */
    public static function widgetPay($private_key, $public_key, $amount, $order_id, $description)
    {
        $epoint = static::instantiate($private_key, $public_key);

        $epoint->order_id = $order_id;
        $epoint->amount = $amount;
        $epoint->description = $description;

        $epoint->getWidgetUrl();

        return $epoint->response;
    }

    /**
     * Callback imzasını doğrula
     */
    public static function verifyCallback($private_key, $public_key, $data, $signature)
    {
        $epoint = new self([
            'private_key' => $private_key,
            'public_key' => $public_key,
            'data' => $data,
            'signature' => $signature,
        ]);

        return $epoint->isSignatureValid();
    }

    public static function decodeCallbackData($data): ?object
    {
        $json = base64_decode($data, true);

        if ($json === false) {
            return null;
        }

        $payload = json_decode($json);

        return is_object($payload) ? $payload : null;
    }
}
