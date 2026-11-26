<?php

namespace App\Services\Apps;

use App\Contracts\AppProvisioner;
use App\Models\App;
use RuntimeException;

class AppProvisionerRegistry
{
    public function resolve(App $app): AppProvisioner
    {
        $provisioners = config(
            'app_enrolment.provisioners',
            []
        );

        $class = $provisioners[$app->slug] ?? null;

        if (! is_string($class) || ! class_exists($class)) {
            throw new RuntimeException(
                "No provisioner is configured for {$app->slug}."
            );
        }

        $provisioner = app($class);

        if (! $provisioner instanceof AppProvisioner) {
            throw new RuntimeException(
                "Invalid provisioner configured for {$app->slug}."
            );
        }

        return $provisioner;
    }
}
