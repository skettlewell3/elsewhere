<?php

namespace Tests\Feature\Auth;

use App\Services\Auth\LoginRateLimiter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class LoginRateLimiterTest extends TestCase
{
    private LoginRateLimiter $limiter;

    protected function setUp(): void
    {
        parent::setUp();

        // Never use the Supabase database cache in these tests.
        config()->set('cache.default', 'array');
        config()->set('cache.limiter', 'array');

        $this->limiter = app(LoginRateLimiter::class);
    }

    private function loginRequest(
        string $email = 'test@example.com',
        string $ip = '192.0.2.10'
    ): Request {
        return Request::create(
            '/login',
            'POST',
            ['email' => $email],
            [],
            [],
            ['REMOTE_ADDR' => $ip]
        );
    }

    public function test_five_failed_attempts_trigger_lockout(): void
    {
        $request = $this->loginRequest();

        $this->assertFalse(
            $this->limiter->isLimited($request)
        );

        for ($i = 0; $i < 5; $i++) {
            $this->limiter->recordFailure($request);
        }

        $this->assertTrue(
            $this->limiter->isLimited($request)
        );
    }

    public function test_different_emails_have_separate_failure_limits(): void
    {
        $first = $this->loginRequest('first@example.com');
        $second = $this->loginRequest('second@example.com');

        for ($i = 0; $i < 5; $i++) {
            $this->limiter->recordFailure($first);
        }

        $this->assertTrue(
            $this->limiter->isLimited($first)
        );

        $this->assertFalse(
            $this->limiter->isLimited($second)
        );
    }

    public function test_ip_request_limit_blocks_after_thirty_requests(): void
    {
        $request = $this->loginRequest();

        for ($i = 0; $i < 30; $i++) {
            $this->limiter->recordRequest($request);
        }

        $this->assertTrue(
            $this->limiter->isLimited($request)
        );

        $this->assertTrue(
            RateLimiter::tooManyAttempts(
                $this->limiter->requestKey($request),
                30
            )
        );
    }

    public function test_clearing_failures_removes_email_lockout(): void
    {
        $request = $this->loginRequest();

        for ($i = 0; $i < 5; $i++) {
            $this->limiter->recordFailure($request);
        }

        $this->assertTrue(
            $this->limiter->isLimited($request)
        );

        $this->limiter->clearFailures($request);

        $this->assertFalse(
            $this->limiter->isLimited($request)
        );
    }

    public function test_locked_out_user_receives_retry_time(): void
    {
        $request = $this->loginRequest();

        for ($i = 0; $i < 5; $i++) {
            $this->limiter->recordFailure($request);
        }

        $this->assertGreaterThan(
            0,
            $this->limiter->retryAfter($request)
        );
    }
}
