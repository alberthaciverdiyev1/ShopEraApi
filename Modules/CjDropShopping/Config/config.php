<?php

return [
    'name' => 'CjDropShopping',
    'icon' => 'CjDropShopping',

    /*
    |--------------------------------------------------------------------------
    | CJ Dropshipping Open API
    |--------------------------------------------------------------------------
    |
    | Credentials are issued from the CJ Dropshipping dashboard
    | (https://developers.cjdropshipping.com). Authentication expects the
    | account e-mail plus the generated API key used as the password.
    |
    */
    'base_url' => env('CJ_DROPSHIPPING_BASE_URL', 'https://developers.cjdropshipping.com/api2.0/v1'),
    'email' => env('CJ_DROPSHIPPING_EMAIL'),
    'api_key' => env('CJ_DROPSHIPPING_API_KEY'),
    'timeout' => (int) env('CJ_DROPSHIPPING_TIMEOUT', 20),

    /*
    | Access tokens are cached (tenant-scoped) until they expire. Tokens are
    | refreshed this many seconds early so an in-flight request never uses a
    | token that expires mid-call.
    */
    'token_cache_buffer' => (int) env('CJ_DROPSHIPPING_TOKEN_BUFFER', 300),
];
