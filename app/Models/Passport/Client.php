<?php

namespace App\Models\Passport;

use Illuminate\Contracts\Auth\Authenticatable;
use Laravel\Passport\Client as BaseClient;
use Laravel\Passport\Scope;

class Client extends BaseClient
{
    /**
     * Determine whether a trusted Elsewhere-owned application
     * can bypass the OAuth consent screen.
     *
     * @param  Scope[]  $scopes
     */
    public function skipsAuthorization(
        Authenticatable $user,
        array $scopes
    ): bool {
        $trustedClientIds = config(
            'elsewhere_oauth.trusted_client_ids',
            []
        );

        return in_array(
            (string) $this->getKey(),
            $trustedClientIds,
            true
        );
    }
}
