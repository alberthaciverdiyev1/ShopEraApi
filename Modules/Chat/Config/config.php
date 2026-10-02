<?php

return [
    'name' => 'Chat',
    'icon' => 'Chat',

    // Phone of the account that owns storefront (customer ↔ support) conversations.
    // When empty, the first account with the `admin` role is used.
    'support_admin_phone' => env('CHAT_SUPPORT_ADMIN_PHONE'),
];
