<?php

return [
    'trusted_client_ids' => array_values(array_filter([
        env('PERFECT10_OAUTH_CLIENT_ID'),
    ])),

    'perfect10_url' => env('PERFECT10_APP_URL'),
];
