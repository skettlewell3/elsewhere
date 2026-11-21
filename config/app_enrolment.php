<?php

use App\Services\Apps\Perfect10Provisioner;

return [

    'enabled' => env('APP_ENROLMENT_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Application Provisioners
    |--------------------------------------------------------------------------
    |
    | Each application is responsible for creating its own profile.
    |
    */

    'provisioners' => [
        'perfect10' => Perfect10Provisioner::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Provisioning Authentication
    |--------------------------------------------------------------------------
    |
    | The same signing key must be configured in Perfect10.
    | Keep the key outside source control.
    |
    */

    'signing_key' => env('ELSEWHERE_PROVISIONING_SIGNING_KEY'),

    /*
    |--------------------------------------------------------------------------
    | HTTP Timeout
    |--------------------------------------------------------------------------
    */

    'timeout' => 10,
];
