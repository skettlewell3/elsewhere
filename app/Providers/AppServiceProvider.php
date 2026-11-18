<?php

namespace App\Providers;

use App\Models\Passport\Client;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Passport::useClientModel(Client::class);

        Passport::tokensCan([
            'identity:read' => 'Read your Elsewhere account identity',
        ]);

        Passport::authorizationView('auth.oauth.authorise');
    }
}
