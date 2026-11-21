<?php

namespace App\Contracts;

use App\Models\AccountUser;
use App\Models\App;

interface AppProvisioner
{
    public function provision(
        AccountUser $user,
        App $app
    ): void;
}
