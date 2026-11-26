<?php

namespace Tests\Feature;

use App\Models\AccountUser;
use App\Models\App;
use App\Services\Apps\Perfect10Provisioner;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class Perfect10ProvisionerTest extends TestCase
{
    private const URL =
        'http://localhost:8001/api/integrations/elsewhere/profiles';

    private const AUTH_ID =
        '11111111-1111-4111-8111-111111111111';

    private const PROFILE_ID =
        '22222222-2222-4222-8222-222222222222';

    private const SIGNING_KEY =
        '0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set(
            'elsewhere_oauth.perfect10_url',
            'http://localhost:8001'
        );

        config()->set(
            'app_enrolment.signing_key',
            self::SIGNING_KEY
        );

        config()->set('app_enrolment.timeout', 10);

        Http::preventStrayRequests();
    }

    private function user(): AccountUser
    {
        return new AccountUser([
            'auth_id' => self::AUTH_ID,
            'account_id' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa',
            'username' => 'test_player',
        ]);
    }

    private function app(): App
    {
        return new App([
            'slug' => 'perfect10',
            'name' => 'Perfect10',
            'status' => 'active',
        ]);
    }

    private function provision(): void
    {
        app(Perfect10Provisioner::class)->provision(
            $this->user(),
            $this->app()
        );
    }

    private function successfulResponse(): array
    {
        return [
            'profile_id' => self::PROFILE_ID,
            'provisioned' => true,
        ];
    }

    public function test_successful_provisioning(): void
    {
        Http::fake([
            self::URL => Http::response(
                $this->successfulResponse(),
                200
            ),
        ]);

        $this->provision();

        Http::assertSentCount(1);
    }

    public function test_request_contains_correct_identity_and_signature(): void
    {
        Http::fake([
            self::URL => Http::response(
                $this->successfulResponse(),
                200
            ),
        ]);

        $this->provision();

        Http::assertSent(function ($request): bool {
            $body = $request->body();

            $payload = json_decode(
                $body,
                true,
                512,
                JSON_THROW_ON_ERROR
            );

            $timestamp = $request->header(
                'X-Elsewhere-Timestamp'
            )[0] ?? null;

            $nonce = $request->header(
                'X-Elsewhere-Nonce'
            )[0] ?? null;

            $signature = $request->header(
                'X-Elsewhere-Signature'
            )[0] ?? null;

            if (
                ! is_string($timestamp) ||
                ! is_string($nonce) ||
                ! is_string($signature)
            ) {
                return false;
            }

            $message = $timestamp."\n"
                .$nonce."\n"
                .$body;

            $expectedSignature = hash_hmac(
                'sha256',
                $message,
                hex2bin(self::SIGNING_KEY)
            );

            return $request->method() === 'POST'
                && $request->url() === self::URL
                && $payload === [
                    'auth_id' => self::AUTH_ID,
                    'username' => 'test_player',
                ]
                && abs(time() - (int) $timestamp) <= 300
                && preg_match(
                    '/\A[a-f0-9]{32}\z/',
                    $nonce
                ) === 1
                && hash_equals(
                    $expectedSignature,
                    $signature
                );
        });
    }

    public function test_server_error_rejects_provisioning(): void
    {
        Http::fake([
            self::URL => Http::response([
                'message' => 'Provisioning unavailable.',
            ], 503),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Perfect10 profile provisioning failed.'
        );

        $this->provision();
    }

    public function test_invalid_response_rejects_provisioning(): void
    {
        Http::fake([
            self::URL => Http::response([
                'profile_id' => 'invalid-profile-id',
                'provisioned' => true,
            ], 200),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Perfect10 returned an invalid provisioning response.'
        );

        $this->provision();
    }

    public function test_missing_signing_key_rejects_provisioning(): void
    {
        config()->set('app_enrolment.signing_key', null);

        Http::fake();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Perfect10 provisioning signing key is not configured.'
        );

        try {
            $this->provision();
        } finally {
            Http::assertNothingSent();
        }
    }

    public function test_missing_provisioning_url_rejects_provisioning(): void
    {
        config()->set(
            'elsewhere_oauth.perfect10_url',
            null
        );

        Http::fake();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Perfect10 provisioning URL is not configured.'
        );

        try {
            $this->provision();
        } finally {
            Http::assertNothingSent();
        }
    }

    public function test_network_failure_does_not_report_success(): void
    {
        Http::fake(function () {
            throw new ConnectionException(
                'Connection refused'
            );
        });

        $this->expectException(ConnectionException::class);

        $this->provision();
    }
}
