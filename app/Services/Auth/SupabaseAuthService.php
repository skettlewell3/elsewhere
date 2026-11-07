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
