<?php

return [
    'name' => 'CjDropShopping',
    'icon' => 'CjDropShopping',

    /*
    |--------------------------------------------------------------------------
    | CJ Dropshipping Open API
    |--------------------------------------------------------------------------
    |
    | The API key is issued from the CJ Dropshipping dashboard
    | (https://developers.cjdropshipping.com). Authentication posts only this
    | key to `authentication/getAccessToken`; no account e-mail is required.
    |
    */
    'base_url' => env('CJ_DROPSHIPPING_BASE_URL', 'https://developers.cjdropshipping.com/api2.0/v1'),
    'api_key' => env('CJ_DROPSHIPPING_API_KEY'),
    'timeout' => (int) env('CJ_DROPSHIPPING_TIMEOUT', 20),

    /*
    | Imported product prices = the cheapest CJ variant price + this markup
    | percentage. 0 keeps CJ's USD price as-is.
    */
    'price_markup_percent' => (float) env('CJ_DROPSHIPPING_PRICE_MARKUP', 0),

    /*
    | Access tokens are cached (tenant-scoped) until they expire. Tokens are
    | refreshed this many seconds early so an in-flight request never uses a
    | token that expires mid-call.
    */
    'token_cache_buffer' => (int) env('CJ_DROPSHIPPING_TOKEN_BUFFER', 300),
];
