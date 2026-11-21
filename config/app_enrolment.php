
<?php

use App\Services\Apps\Perfect10Provisioner;

return [
    /*
    |--------------------------------------------------------------------------
    | Application Provisioners
    |--------------------------------------------------------------------------
    |
    | Each application registers the service responsible for creating
    | its own application-specific profile.
    |
    | The Elsewhere enrolment process remains the same for every app.
    |
    */

    'provisioners' => [
        'perfect10' => Perfect10Provisioner::class,
    ],
];
