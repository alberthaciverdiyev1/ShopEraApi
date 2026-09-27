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
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
        // The web client the apps ask Google to issue ID tokens for. It has to
        // live in the Google Cloud project that holds the Android client
        // (package + signing SHA-1), so it can differ from client_id.
        'app_server_client_id' => env('GOOGLE_APP_SERVER_CLIENT_ID', env('GOOGLE_CLIENT_ID')),
        // The iOS OAuth client, handed to the app with the switches below.
        'ios_client_id' => env('GOOGLE_IOS_CLIENT_ID'),
        // Any other OAuth clients of ours an ID token may be issued for
        // (comma-separated). client_id and app_server_client_id always count.
        'native_client_ids' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('GOOGLE_NATIVE_CLIENT_IDS', ''))
        ))),
    ],
    'apple' => [
        // Sign in with Apple identity tokens are issued for the iOS app's
        // bundle id (comma-separated if there is ever more than one app).
        'client_ids' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('APPLE_CLIENT_IDS', 'teymurstore'))
        ))),
        // The Sign in with Apple key (Certificates, IDs & Profiles -> Keys).
        // The server signs its calls to Apple with it: exchanging a sign-in's
        // authorization code for a refresh token, and revoking that token when
        // the account is deleted. The .p8 file stays out of the repository.
        'team_id' => env('APPLE_TEAM_ID'),
        'key_id' => env('APPLE_KEY_ID'),
        'private_key_path' => env('APPLE_PRIVATE_KEY_PATH'),
    ],
    // Which sign-in buttons the apps show (GET /api/auth/social-login). Each
    // stays off until it is set up and tested, so a store build can carry the
    // buttons hidden and they appear without another release.
    'social_login' => [
        'google_android' => (bool) env('SOCIAL_LOGIN_GOOGLE_ANDROID', false),
        'google_ios' => (bool) env('SOCIAL_LOGIN_GOOGLE_IOS', false),
        'apple_ios' => (bool) env('SOCIAL_LOGIN_APPLE_IOS', false),
    ],
    'otp' => [
        'login'=>env('OTP_LOGIN'),
        'password'=>env('OTP_PASSWORD'),
        'sender'=>env('OTP_SENDER','LSIM'),
    ],
    'ai' => [
        'driver' => env('AI_DRIVER', 'ollama'),
        'api_key' => env('AI_API_KEY'),
        'ollama_url' => env('OLLAMA_BASE_URL', 'http://localhost:11434'),
        'model' => env('OLLAMA_MODEL', 'llava'),
    ],
    'starex' => [
        'enabled' => (bool) env('STAREX_ENABLED', false),
        'base_url' => env('STAREX_BASE_URL', 'https://api.starexglobal.com'),
        'email' => env('STAREX_EMAIL'),
        'password' => env('STAREX_PASSWORD'),
        'webhook_secret' => env('STAREX_WEBHOOK_SECRET'),
        'default_weight' => (float) env('STAREX_DEFAULT_PRODUCT_WEIGHT', 1),
        'default_region_id' => env('STAREX_DEFAULT_REGION_ID'),
        'home_delivery_type' => env('STAREX_HOME_DELIVERY_TYPE', 'home_delivery'),
        'pudo_delivery_type' => env('STAREX_PUDO_DELIVERY_TYPE', 'pudo_delivery'),
        'package_type' => env('STAREX_PACKAGE_TYPE', 'general_goods'),
        'currency' => env('STAREX_CURRENCY', 'azn'),
        'pickup_delivery_time' => env('STAREX_PICKUP_DELIVERY_TIME', '2-3 gün'),
    ],
    'app_links' => [
        'android_package_name' => env('ANDROID_APP_PACKAGE_NAME', 'com.app.teymur'),
        'android_sha256_cert_fingerprints' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('ANDROID_APP_CERT_SHA256', ''))
        ))),
        'ios_app_ids' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('IOS_APP_IDS', 'ZM73R29G8N.teymurstore'))
        ))),
    ],


];
