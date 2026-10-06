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
    |--------------------------------------------------------------------------
    | Variant parsing
    |--------------------------------------------------------------------------
    |
    | CJ exposes no dedicated colour/size fields, only a free-text variantKey,
    | so options are parsed best-effort. These lists are intentionally data,
    | not code — extend them as your catalogue grows. `auto_colors`/`auto_sizes`
    | can be switched off if a catalogue yields wrong options.
    |
    | On top of these, words taken from the product's own title/category are
    | ignored as colours automatically (see DProductService), so new products
    | adapt without editing anything here.
    |
    */
    'variant' => [
        'auto_colors' => (bool) env('CJ_DROPSHIPPING_AUTO_COLORS', true),
        'auto_sizes' => (bool) env('CJ_DROPSHIPPING_AUTO_SIZES', true),

        'size_tokens' => [
            'XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL', '2XL', '3XL', '4XL', '5XL', 'FREE SIZE', 'ONE SIZE',
        ],

        'color_stopwords' => [
            'hoodie', 'sweatshirt', 'shirt', 'shirts', 'jacket', 'coat', 'pants', 'dress', 'set', 'sets',
            'weft', 'bundle', 'bundles', 'wig', 'wigs', 'straight', 'curly', 'wave', 'wavy', 'socks', 'lamp',
            'tool', 'tools', 'cap', 'hat', 'bag', 'shoes', 'sneakers', 'size', 'color', 'colour', 'piece',
            'pieces', 'pc', 'pcs', 'style', 'model', 'type', 'free', 'one', 'plus', 'new', 'hot', 'high',
            'quality', 'fashion', 'casual', 'slim', 'loose', 'oversize', 'regular',
        ],

        // A word from the product title/category is only ignored as a colour if
        // it is NOT on this list — so "Black Hoodie" still yields a Black option.
        'color_allowlist' => [
            'black', 'white', 'red', 'blue', 'green', 'yellow', 'pink', 'purple', 'orange', 'brown',
            'grey', 'gray', 'silver', 'gold', 'beige', 'navy', 'maroon', 'cyan', 'magenta', 'violet',
            'indigo', 'teal', 'olive', 'lime', 'coral', 'ivory', 'cream', 'khaki', 'burgundy',
            'turquoise', 'lavender', 'peach', 'mint', 'rose', 'charcoal', 'bronze', 'copper',
            'multicolor', 'multi', 'transparent', 'clear', 'nude', 'apricot', 'wine',
        ],
    ],

    /*
    | Access tokens are cached (tenant-scoped) until they expire. Tokens are
    | refreshed this many seconds early so an in-flight request never uses a
    | token that expires mid-call.
    */
    'token_cache_buffer' => (int) env('CJ_DROPSHIPPING_TOKEN_BUFFER', 300),
];
