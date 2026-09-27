<?php

return [
    'name' => 'Listing',

    // How long an ad stays visible before it moves to the owner's archive.
    // A section may override it.
    'default_duration_days' => 30,

    'media' => [
        'max_images' => 15,
        // Per request, not per ad. PHP-FPM stops at 30 s and every photo is
        // resized and pushed to the CDN, so the app sends them in batches.
        'max_per_request' => 5,
        // A phone photo is a few MB; anything much larger is a camera file
        // the app should have shrunk, and GD would load it whole into the
        // 128 MB the pool allows.
        'max_image_bytes' => 8 * 1024 * 1024,
        // The proposal's limit: bigger files cost upload time on a phone and
        // disk on a server that also stores product photos.
        'max_video_bytes' => 10 * 1024 * 1024,
    ],

    /*
     * The map behind the "location" field.
     *
     * The tile address is configuration rather than code because every free
     * raster source has its own terms: OpenStreetMap's own servers are fine
     * for development but their tile policy does not allow a published app to
     * use them, so before a store release this should point at a keyed free
     * tier (MapTiler, Stadia) or at our own cache. Changing it is a line in
     * .env plus `config:cache` - no new app build.
     */
    'map' => [
        'tile_url' => env('LISTING_MAP_TILE_URL', 'https://tile.openstreetmap.org/{z}/{x}/{y}.png'),
        'attribution' => env('LISTING_MAP_ATTRIBUTION', '© OpenStreetMap'),
        'max_zoom' => (int) env('LISTING_MAP_MAX_ZOOM', 19),
        // Bakı: the form opens here when the ad has no point yet.
        'default_lat' => (float) env('LISTING_MAP_DEFAULT_LAT', 40.4093),
        'default_lng' => (float) env('LISTING_MAP_DEFAULT_LNG', 49.8671),
    ],

    // VIP exists as a mechanism from the start, but it hands out nothing until
    // payments are switched on, so no money flow is implied by shipping it.
    'vip' => [
        'paid' => false,
        'days' => 7,
    ],
];
