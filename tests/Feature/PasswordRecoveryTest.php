<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PasswordRecoveryTest extends TestCase
{
    private const
        TOKEN_HASH = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        config()->set(
            'services.supabase.url',
            'https://example.supabase.co'
        );

        config()->set(
            'services.supabase.publishable_key',
            'test-publishable-key'
        );
    }

    public function test_forgot_password_form_is_available(): void
    {
        $this->get(route('password.request'))
            ->assertOk()
            ->assertSee('Send reset link');
    }

    public function test_recovery_request_uses_local_reset_redirect(): void
    {
        Http::preventStrayRequests();

        Http::fake([
            '*/auth/v1/recover*' => Http::response([], 200),
        ]);

        $this->post(route('password.email'), [
            'email' => 'test@example.com',
        ])
            ->assertRedirect()
            ->assertSessionHas('recovery_sent');

        Http::assertSent(function ($request) {
            return $request->method() === 'POST'
                && str_contains(
                    $request->url(),
                    '/auth/v1/recover?redirect_to='
                )
                && str_contains(
                    rawurldecode($request->url()),
                    '/reset-password'
                )
                && $request['email'] === 'test@example.com';
        });
    }

    public function test_recovery_request_requires_valid_email(): void
    {
        Http::preventStrayRequests();
        Http::fake();

        $this->post(route('password.email'), [
            'email' => 'not-an-email',
        ])
            ->assertSessionHasErrors('email');

        Http::assertNothingSent();
    }

    public function test_opening_a_reset_link_does_not_consume_it(): void
    {
        Http::preventStrayRequests();
        Http::fake();

        $this->get(route('password.reset', [
            'token_hash' => self::TOKEN_HASH,
            'type' => 'recovery',
        ]))
            ->assertOk()
            ->assertSee('Reset password')
            ->assertSee('name="token_hash"', false)
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertHeader('Referrer-Policy', 'no-referrer');

        Http::assertNothingSent();
    }

    public function test_reset_form_rejects_missing_token(): void
    {
        $this->get(route('password.reset'))
            ->assertOk()
            ->assertSee('missing or invalid')
            ->assertSee('Request another reset link')
            ->assertDontSee('name="token_hash"', false);
    }

    public function test_password_reset_verifies_token_and_updates_password(): void
    {
        Http::preventStrayRequests();

        Http::fake([
            '*/auth/v1/verify' => Http::response([
                'access_token' => 'temporary-recovery-access-token',
                'refresh_token' => 'temporary-recovery-refresh-token',
                'token_type' => 'bearer',
            ], 200),

            '*/auth/v1/user' => Http::response([
                'id' => '11111111-1111-4111-8111-111111111111',
            ], 200),
        ]);

        $this->post(route('password.update'), [
            'token_hash' => self::TOKEN_HASH,
            'type' => 'recovery',
            'password' => 'NewTestPassword123!',
            'password_confirmation' => 'NewTestPassword123!',
        ])
            ->assertRedirect(route('login'))
            ->assertSessionHas('password_reset_success');

        Http::assertSent(function ($request) {
            return $request->method() === 'POST'
                && str_ends_with(
                    $request->url(),
                    '/auth/v1/verify'
                )
                && $request['type'] === 'recovery'
                && $request['token_hash'] === self::TOKEN_HASH;
        });

        Http::assertSent(function ($request) {
            return $request->method() === 'PUT'
                && str_ends_with(
                    $request->url(),
                    '/auth/v1/user'
                )
                && $request->hasHeader(
                    'Authorization',
                    'Bearer temporary-recovery-access-token'
                )
                && $request['password'] === 'NewTestPassword123!';
        });

        Http::assertSentCount(2);
    }

    public function test_invalid_recovery_token_does_not_update_password(): void
    {
        Http::preventStrayRequests();

        Http::fake([
            '*/auth/v1/verify' => Http::response([
                'error_code' => 'otp_expired',
            ], 403),
        ]);

        $this->post(route('password.update'), [
            'token_hash' => self::TOKEN_HASH,
            'type' => 'recovery',
            'password' => 'NewTestPassword123!',
            'password_confirmation' => 'NewTestPassword123!',
        ])
            ->assertRedirect(route('password.request'))
            ->assertSessionHasErrors('recovery');

        Http::assertSentCount(1);
    }

    public function test_password_validation_prevents_token_verification(): void
    {
        Http::preventStrayRequests();
        Http::fake();

        $this->post(route('password.update'), [
            'token_hash' => self::TOKEN_HASH,
            'type' => 'recovery',
            'password' => 'short',
            'password_confirmation' => 'different',
        ])
            ->assertSessionHasErrors('password');

        Http::assertNothingSent();
    }
}
