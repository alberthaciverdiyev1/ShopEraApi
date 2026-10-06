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

    // CJ's QPS limit is 1 request/second; space authenticated calls out by this
    // many milliseconds. Set 0 to disable (e.g. in tests).
    'request_interval_ms' => (int) env('CJ_DROPSHIPPING_REQUEST_INTERVAL', 1100),

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
    /*
    |--------------------------------------------------------------------------
    | Re-sync behaviour
    |--------------------------------------------------------------------------
    |
    | On re-import these `products` columns are never overwritten, so local
    | edits survive a sync (price is the usual one). Add more columns if you
    | also edit them locally, e.g. title, description, category_id, brand_id,
    | sku, weight, discount. Stock is always refreshed.
    |
    */
    'import' => [
        'protected_fields' => ['price', 'discount', 'is_active', 'approval_status'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Specification filters
    |--------------------------------------------------------------------------
    |
    | CJ product specs live as "Key: Value" lines in the description. Every key
    | (except these) becomes a dynamic Filter attached to the product and its
    | category, so specs work catalogue-wide without code changes.
    |
    */
    'spec' => [
        'max_keys' => 20,
        'skip_keys' => [
            'product name', 'name', 'color', 'colour', 'size', 'image', 'images',
            'sku', 'model', 'brand', 'product id', 'pid', 'weight',
        ],
    ],

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
            'coffee', 'sunflower', 'champagne', 'rainbow', 'jet black', 'natural black',
        ],

        // Colour name => hex, used when creating a Color row. Extend as needed;
        // multi-word names (e.g. "light green") are resolved first, then the
        // base colour word is used as a fallback.
        'color_hex' => [
            'black' => '#000000', 'white' => '#ffffff', 'red' => '#ff0000', 'blue' => '#0000ff',
            'green' => '#008000', 'yellow' => '#ffff00', 'pink' => '#ffc0cb', 'purple' => '#800080',
            'orange' => '#ffa500', 'brown' => '#a52a2a', 'grey' => '#808080', 'gray' => '#808080',
            'silver' => '#c0c0c0', 'gold' => '#ffd700', 'beige' => '#f5f5dc', 'navy' => '#000080',
            'maroon' => '#800000', 'cyan' => '#00ffff', 'magenta' => '#ff00ff', 'violet' => '#ee82ee',
            'indigo' => '#4b0082', 'teal' => '#008080', 'olive' => '#808000', 'lime' => '#00ff00',
            'coral' => '#ff7f50', 'ivory' => '#fffff0', 'cream' => '#fffdd0', 'khaki' => '#f0e68c',
            'burgundy' => '#800020', 'turquoise' => '#40e0d0', 'lavender' => '#e6e6fa',
            'peach' => '#ffe5b4', 'mint' => '#98ff98', 'rose' => '#ff007f', 'charcoal' => '#36454f',
            'bronze' => '#cd7f32', 'copper' => '#b87333', 'nude' => '#e3bc9a', 'apricot' => '#fbceb1',
            'wine' => '#722f37', 'multi' => '#cccccc', 'multicolor' => '#cccccc',
            'coffee' => '#6f4e37', 'sunflower' => '#ffda03', 'champagne' => '#f7e7ce',
            'army green' => '#4b5320', 'fluorescent green' => '#7fff00', 'dark brown' => '#654321',
            // Common qualifier + base combinations.
            'light green' => '#90ee90', 'dark green' => '#006400', 'light blue' => '#add8e6',
            'dark blue' => '#00008b', 'light pink' => '#ffb6c1', 'light grey' => '#d3d3d3',
            'dark grey' => '#a9a9a9', 'light gray' => '#d3d3d3', 'dark gray' => '#a9a9a9',
            'light yellow' => '#ffffe0', 'dark red' => '#8b0000', 'light purple' => '#dda0dd',
            'dark purple' => '#301934', 'sky blue' => '#87ceeb', 'royal blue' => '#4169e1',
            'hot pink' => '#ff69b4', 'navy blue' => '#000080', 'baby blue' => '#89cff0',
            'deep blue' => '#00008b',
        ],
    ],

    /*
    | Access tokens are cached (tenant-scoped) until they expire. Tokens are
    | refreshed this many seconds early so an in-flight request never uses a
    | token that expires mid-call.
    */
    'token_cache_buffer' => (int) env('CJ_DROPSHIPPING_TOKEN_BUFFER', 300),
];
