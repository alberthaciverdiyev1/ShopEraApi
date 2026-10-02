<?php

return [
    'name' => 'Manager',
    'icon' => 'Manager',

    // Base domain used to auto-suggest a tenant subdomain when an owner has no
    // explicit host (e.g. "redbull" -> "redbull.snaker.store"). DNS/nginx is
    // managed manually; this is only a naming suggestion.
    'base_domain' => env('MANAGER_BASE_DOMAIN', 'snaker.store'),
];
