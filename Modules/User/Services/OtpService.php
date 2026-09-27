<?php

namespace Modules\User\Services;

use Exception;
use Illuminate\Support\Facades\Http;

class OtpService
{
    public ?string $login;
    public ?string $password;
    public ?string $sender;

    protected string $apiUrl = 'https://apps.lsim.az/quicksms/v1/send';
    protected string $balanceUrl = 'https://apps.lsim.az/quicksms/v1/balance';
    protected string $checkNumberUrl = 'https://apps.lsim.az/lsimrest/mnp/api/get';

    public function __construct()
    {
        $this->login = config('services.otp.login');
        $this->password = config('services.otp.password');
        $this->sender = config('services.otp.sender');
    }

    protected function calculateKey($login, $password): string
    {
        $md5Password = md5($password);
        return md5($md5Password . $login);
    }

    public function sendSms($msisdn, $text, $unicode = false): string
    {
        if ($this->login && $this->password && $this->sender) {

            $msisdn = '994' . substr($msisdn, 1);

            $md5Password = md5($this->password);
            $key = md5($md5Password . $this->login . $text . $msisdn . $this->sender);

            $params = [
                'login' => $this->login,
                'msisdn' => $msisdn,
                'text' => $text,
                'sender' => $this->sender,
                'key' => $key,
                'unicode' => $unicode,
            ];

            try {
                $response = Http::get($this->apiUrl, $params);

                if ($response->successful()) {
                    return $response->body();
                } else {
                    return $this->getResponseErrorMessage($response);
                }

            } catch (Exception $exception) {
                return $exception->getMessage();
            }
        } else {
            return __('Otp not configured!');
        }

    }

    public function checkBalance(): string|array
    {
        if ($this->login && $this->password && $this->sender) {
            $key = $this->calculateKey($this->login, $this->password);

            $params = [
                'login' => $this->login,
                'key' => $key,
            ];

            try {
                $response = Http::get($this->balanceUrl, $params);

                if ($response->successful()) {
                    $data = $response->json();

                    return $data;
                } else {
                    return $this->getResponseErrorMessage($response);
                }

            } catch (Exception $exception) {
                return [
                    'error' => true,
                    'message' => $exception->getMessage(),
                ];
            }
        } else {
            return __('Otp not configured!');
        }
    }


    public function checkNumber($msisdn): string
    {
        if ($this->login && $this->password && $this->sender) {
            $params = [
                'username' => $this->login,
                'password' => $this->password,
                'msisdn' => $msisdn,
            ];

            try {
                $response = Http::get($this->checkNumberUrl, $params);

                if ($response->successful()) {
                    return $response->body();
                } else {
                    return $this->getResponseErrorMessage($response);
                }
            } catch (Exception $exception) {
                return $exception->getMessage();
            }
        } else {
            return __('Otp not configured!');
        }
    }

    protected function getResponseErrorMessage($response): string
    {
        if ($this->login && $this->password && $this->sender) {
            $statusCode = $response->status();
            $body = $response->body();

            return "HTTP {$statusCode} - {$body}";
        }
            return __('Otp not configured!');
    }
}

