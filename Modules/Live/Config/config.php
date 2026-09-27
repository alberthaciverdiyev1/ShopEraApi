<?php

return [
    'name' => 'Live',

    /* Days a finished stream stays watchable inside the app. */
    'replay_days' => 3,

    'chat' => [
        'max_length' => 300,
        'history_limit' => 50,

        /* Seconds a viewer must wait between two messages. */
        'throttle_seconds' => 2,
    ],
];
