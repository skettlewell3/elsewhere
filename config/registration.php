<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Public Registration
    |--------------------------------------------------------------------------
    |
    | Keep disabled until the Supabase Before User Created
    | hook and email confirmation flow have been verified.
    |
    */

    'enabled' => env('ELSEWHERE_REGISTRATION_ENABLED', false),
];
