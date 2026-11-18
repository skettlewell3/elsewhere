<?php

namespace Tests\Feature\Auth;

use App\Models\Passport\Client;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TrustedOAuthClientTest extends TestCase
{
    #[Test]
    public function trusted_elsewhere_clients_skip_oauth_consent(): void
    {
        config()->set('elsewhere_oauth.trusted_client_ids', [
            'trusted-perfect10-client',
        ]);

        $client = new Client();
        $client->setAttribute('id', 'trusted-perfect10-client');

        $this->assertTrue(
            $client->skipsAuthorization(new User(), [])
        );
    }

    #[Test]
    public function external_oauth_clients_require_consent(): void
    {
        config()->set('elsewhere_oauth.trusted_client_ids', [
            'trusted-perfect10-client',
        ]);

        $client = new Client();
        $client->setAttribute('id', 'external-app-client');

        $this->assertFalse(
            $client->skipsAuthorization(new User(), [])
        );
    }

    #[Test]
    public function oauth_consent_is_required_when_no_clients_are_trusted(): void
    {
        config()->set('elsewhere_oauth.trusted_client_ids', []);

        $client = new Client();
        $client->setAttribute('id', 'trusted-perfect10-client');

        $this->assertFalse(
            $client->skipsAuthorization(new User(), [])
        );
    }
}
