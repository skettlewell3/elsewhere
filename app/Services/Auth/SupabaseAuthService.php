<?php

namespace App\Services\Auth;

use App\Exceptions\SupabaseLoginException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class SupabaseAuthService
{
    public function signIn(string $email, string $password): array
    {
        $url = rtrim(config('services.supabase.url'), '/');
        $key = config('services.supabase.publishable_key');

        try {
            $response = Http::withHeaders([
                'apikey' => $key,
            ])
                ->timeout(10)
                ->post(
                    "{$url}/auth/v1/token?grant_type=password",
                    [
                        'email' => $email,
                        'password' => $password,
                    ]
                );
        } catch (ConnectionException $exception) {
            throw new SupabaseLoginException('unavailable');
        }

        if ($response->failed()) {
            $errorCode = $response->json('error_code')
                ?? $response->json('code');

            if (
                $errorCode === 'invalid_credentials' ||
                (
                    $response->status() === 400 &&
                    $errorCode === null
                )
            ) {
                throw new SupabaseLoginException(
                    'invalid_credentials'
                );
            }

            if ($errorCode === 'email_not_confirmed') {
                throw new SupabaseLoginException(
                    'email_not_confirmed'
                );
            }

            if ($response->status() === 429) {
                throw new SupabaseLoginException(
                    'rate_limited'
                );
            }

            if ($response->serverError()) {
                throw new SupabaseLoginException(
                    'unavailable'
                );
            }

            throw new SupabaseLoginException('unexpected');
        }

        $result = $response->json();

        if (
            ! is_array($result) ||
            empty($result['user']['id']) ||
            empty($result['access_token']) ||
            empty($result['refresh_token']) ||
            ! isset($result['expires_at'])
        ) {
            throw new SupabaseLoginException('unexpected');
        }

        return $result;
    }

    /**
     * Submit a signup request exactly once.
     *
     * An accepted HTTP response is not independent proof
     * that the identity exists in auth.users.
     *
     * Possible outcomes:
     * - response_received
     * - rejected
     * - uncertain
     *
     * The caller must verify any proposed identity
     * before finalising registration.
     */
    public function signUp(
        string $email,
        string $password,
        string $firstName,
        string $lastName,
        string $reservationToken
    ): array {
        $url = rtrim(config('services.supabase.url'), '/');
        $key = config('services.supabase.publishable_key');

        try {
            $response = Http::withHeaders([
                'apikey' => $key,
            ])
                ->acceptJson()
                ->timeout(15)
                ->post(
                    "{$url}/auth/v1/signup",
                    [
                        'email' => $email,
                        'password' => $password,
                        'data' => [
                            'first_name' => $firstName,
                            'last_name' => $lastName,
                            'signup_reservation_token' =>
                                $reservationToken,
                        ],
                    ]
                );
        } catch (ConnectionException $exception) {
            return [
                'outcome' => 'uncertain',
                'auth_id' => null,
                'error_code' => 'connection_failure',
            ];
        }

        if ($response->failed()) {
            $errorCode = $response->json('error_code')
                ?? $response->json('code');

            if (
                $response->status() === 408 ||
                $response->status() === 429 ||
                $response->serverError()
            ) {
                return [
                    'outcome' => 'uncertain',
                    'auth_id' => null,
                    'error_code' => 'supabase_unavailable',
                ];
            }

            return [
                'outcome' => 'rejected',
                'auth_id' => null,
                'error_code' => is_string($errorCode)
                    ? $errorCode
                    : 'signup_rejected',
            ];
        }

        $result = $response->json();

        if (! is_array($result)) {
            return [
                'outcome' => 'uncertain',
                'auth_id' => null,
                'error_code' => 'invalid_supabase_response',
            ];
        }

        // The GoTrue REST endpoint normally returns the
        // user fields directly. Support a nested user
        // response as well for compatibility.
        $user = $result['user'] ?? $result;

        if (! is_array($user)) {
            return [
                'outcome' => 'uncertain',
                'auth_id' => null,
                'error_code' => 'invalid_user_response',
            ];
        }

        $authId = $user['id'] ?? null;

        if (! is_string($authId) || $authId === '') {
            return [
                'outcome' => 'uncertain',
                'auth_id' => null,
                'error_code' => 'missing_auth_id',
            ];
        }

        // Supabase can return an obfuscated user object
        // for an existing account. An explicitly empty
        // identities collection must never be considered
        // confirmation of a newly created identity.
        if (
            array_key_exists('identities', $user) &&
            is_array($user['identities']) &&
            count($user['identities']) === 0
        ) {
            return [
                'outcome' => 'rejected',
                'auth_id' => null,
                'error_code' => 'signup_not_created',
            ];
        }

        return [
            'outcome' => 'response_received',
            'auth_id' => strtolower($authId),
            'error_code' => null,
        ];
    }

    public function signOut(string $accessToken): void
    {
        $url = rtrim(config('services.supabase.url'), '/');
        $key = config('services.supabase.publishable_key');

        Http::withHeaders([
            'apikey' => $key,
            'Authorization' => 'Bearer '.$accessToken,
        ])->post(
            "{$url}/auth/v1/logout?scope=local"
        )->throw();
    }

    public function refreshSession(string $refreshToken): array
    {
        $url = rtrim(config('services.supabase.url'), '/');
        $key = config('services.supabase.publishable_key');

        $response = Http::withHeaders([
            'apikey' => $key,
        ])->post(
            "{$url}/auth/v1/token?grant_type=refresh_token",
            [
                'refresh_token' => $refreshToken,
            ]
        );

        if ($response->failed()) {
            throw new RuntimeException(
                $response->json('msg')
                ?? $response->json('error_description')
                ?? 'Unable to refresh session.'
            );
        }

        return $response->json();
    }
}
