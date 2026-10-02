<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],
    'fcm' => [
        'project_id' => env('FIREBASE_PROJECT_ID'),
        'service_account_path' => env('FIREBASE_SERVICE_ACCOUNT_PATH'),
    ],
    'otp' => [
        'login' => env('OTP_LOGIN'),
        'password' => env('OTP_PASSWORD'),
        'sender' => env('OTP_SENDER', 'LSIM'),
    ],
    'ai' => [
        'driver' => env('AI_DRIVER', 'ollama'),
        'api_key' => env('AI_API_KEY'),
        'ollama_url' => env('OLLAMA_BASE_URL', 'http://localhost:11434'),
        'model' => env('OLLAMA_MODEL', 'llava'),
    ],
    'app_links' => [
        'android_package_name' => env('ANDROID_APP_PACKAGE_NAME', 'com.app.shopera'),
        'android_sha256_cert_fingerprints' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('ANDROID_APP_CERT_SHA256', ''))
        ))),
        'ios_app_ids' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('IOS_APP_IDS', 'ZM73R29G8N.shopera'))
        ))),
    ],

    'manager' => [
        'url' => env('MANAGER_URL'),
        'token' => env('MANAGER_TOKEN'),
        'webhook_secret' => env('MANAGER_WEBHOOK_SECRET'),
        'api_key' => env('MANAGER_API_KEY'),
        'site_host' => env('MANAGER_SITE_HOST'),
    ],

];
