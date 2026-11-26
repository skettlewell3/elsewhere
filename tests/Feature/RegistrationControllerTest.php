<?php

namespace Tests\Feature;

use App\Services\Auth\RegistrationService;
use Illuminate\Support\Facades\Cache;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class RegistrationControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('session.driver', 'array');
        config()->set('cache.default', 'array');
        config()->set('registration.enabled', true);

        Cache::clear();
    }

    private function registrationData(): array
    {
        return [
            'first_name' => 'Test',
            'last_name' => 'Player',
            'email' => 'test@example.com',
            'password' => 'ExamplePassword123!',
            'password_confirmation' => 'ExamplePassword123!',
            'invite_code' => 'TEST-INVITE',
        ];
    }

    private function mockRegistration(array $result): void
    {
        $service = Mockery::mock(RegistrationService::class);

        $service->shouldReceive('register')
            ->once()
            ->with(
                'Test',
                'Player',
                'test@example.com',
                'ExamplePassword123!',
                'TEST-INVITE'
            )
            ->andReturn($result);

        $this->app->instance(
            RegistrationService::class,
            $service
        );
    }

    public function test_registration_is_disabled_by_default(): void
    {
        config()->set('registration.enabled', false);

        $service = Mockery::mock(RegistrationService::class);
        $service->shouldNotReceive('register');

        $this->app->instance(
            RegistrationService::class,
            $service
        );

        $this->post(
            route('register.store'),
            $this->registrationData()
        )->assertStatus(503);
    }

    public function test_registration_requires_valid_fields(): void
    {
        $service = Mockery::mock(RegistrationService::class);
        $service->shouldNotReceive('register');

        $this->app->instance(
            RegistrationService::class,
            $service
        );

        $this->post(route('register.store'), [
            'first_name' => '',
            'last_name' => '',
            'email' => 'invalid-email',
            'password' => 'short',
            'password_confirmation' => 'different',
            'invite_code' => '',
        ])->assertSessionHasErrors([
            'first_name',
            'last_name',
            'email',
            'password',
            'invite_code',
        ]);
    }

    public function test_successful_registration_redirects_to_login(): void
    {
        $this->mockRegistration([
            'status' => 'completed',
            'error_code' => null,
            'attempt_id' => 1,
        ]);

        $this->post(
            route('register.store'),
            $this->registrationData()
        )
            ->assertRedirect(route('login'))
            ->assertSessionHas('registration_success');
    }

    public function test_uncertain_registration_requires_reconciliation(): void
    {
        $this->mockRegistration([
            'status' => 'needs_reconciliation',
            'error_code' => 'supabase_unavailable',
            'attempt_id' => 2,
        ]);

        $this->post(
            route('register.store'),
            $this->registrationData()
        )
            ->assertRedirect(route('register'))
            ->assertSessionHas('registration_pending');
    }

    public function test_invalid_signup_code_is_rejected(): void
    {
        $this->mockRegistration([
            'status' => 'rejected',
            'error_code' => 'invalid_signup_code',
            'attempt_id' => null,
        ]);

        $this->from(route('register'))
            ->post(
                route('register.store'),
                $this->registrationData()
            )
            ->assertRedirect(route('register'))
            ->assertSessionHasErrors('invite_code');
    }

    public function test_unexpected_service_failure_is_handled(): void
    {
        $service = Mockery::mock(RegistrationService::class);

        $service->shouldReceive('register')
            ->once()
            ->andThrow(
                new RuntimeException('Registration unavailable')
            );

        $this->app->instance(
            RegistrationService::class,
            $service
        );

        $this->from(route('register'))
            ->post(
                route('register.store'),
                $this->registrationData()
            )
            ->assertRedirect(route('register'))
            ->assertSessionHasErrors('registration');
    }

    public function test_password_is_not_flushed_back_to_form(): void
    {
        $this->mockRegistration([
            'status' => 'rejected',
            'error_code' => 'invalid_signup_code',
            'attempt_id' => null,
        ]);

        $this->from(route('register'))
            ->post(
                route('register.store'),
                $this->registrationData()
            )
            ->assertSessionMissing('_old_input.password')
            ->assertSessionMissing('_old_input.password_confirmation')
            ->assertSessionMissing('_old_input.invite_code');
    }
}
