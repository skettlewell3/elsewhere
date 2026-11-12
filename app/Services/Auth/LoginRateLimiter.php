<?php

namespace App\Services\Auth;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class LoginRateLimiter
{
    private const MAX_FAILURES = 5;

    private const MAX_REQUESTS = 30;

    private const DECAY_SECONDS = 60;

    public function failureKey(Request $request): string
    {
        $email = Str::lower(
            trim((string) $request->input('email'))
        );

        return 'login:failures:'.hash(
            'sha256',
            $email.'|'.$request->ip()
        );
    }

    public function requestKey(Request $request): string
    {
        return 'login:requests:'.hash(
            'sha256',
            (string) $request->ip()
        );
    }

    public function isLimited(Request $request): bool
    {
        return RateLimiter::tooManyAttempts(
            $this->failureKey($request),
            self::MAX_FAILURES
        ) || RateLimiter::tooManyAttempts(
            $this->requestKey($request),
            self::MAX_REQUESTS
        );
    }

    public function retryAfter(Request $request): int
    {
        $seconds = [];

        if (RateLimiter::tooManyAttempts(
            $this->failureKey($request),
            self::MAX_FAILURES
        )) {
            $seconds[] = RateLimiter::availableIn(
                $this->failureKey($request)
            );
        }

        if (RateLimiter::tooManyAttempts(
            $this->requestKey($request),
            self::MAX_REQUESTS
        )) {
            $seconds[] = RateLimiter::availableIn(
                $this->requestKey($request)
            );
        }

        return max($seconds ?: [0]);
    }

    public function recordRequest(Request $request): void
    {
        RateLimiter::hit(
            $this->requestKey($request),
            self::DECAY_SECONDS
        );
    }

    public function recordFailure(Request $request): void
    {
        RateLimiter::hit(
            $this->failureKey($request),
            self::DECAY_SECONDS
        );
    }

    public function clearFailures(Request $request): void
    {
        RateLimiter::clear(
            $this->failureKey($request)
        );
    }
}
