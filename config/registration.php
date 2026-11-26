<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Public Registration
    |--------------------------------------------------------------------------
    */

    'enabled' => env('ELSEWHERE_REGISTRATION_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Failed Registration Recovery
    |--------------------------------------------------------------------------
    |
    | Allow stale, unresolved registrations to be closed only when
    | the Auth identity, Elsewhere account and redemption are absent.
    |
    | A minimum grace period of 60 minutes is enforced by the service.
    |
    */

    'recovery_enabled' => env(
        'ELSEWHERE_REGISTRATION_RECOVERY_ENABLED',
        false
    ),

    'recovery_grace_minutes' => (int) env(
        'ELSEWHERE_REGISTRATION_RECOVERY_GRACE_MINUTES',
        60
    ),
];
