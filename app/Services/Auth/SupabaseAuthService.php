<?php

namespace App\Services\Auth;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class SupabaseAuthService
{
    public function signIn(string $email, string $password): array
    {
        $url = rtrim(config('services.supabase.url'), '/');
        $key = config('services.supabase.publishable_key');

        $response = Http::withHeaders([
            'apikey' => $key,
        ])->post(
            "{$url}/auth/v1/token?grant_type=password",
            [
                'email' => $email,
                'password' => $password,
            ]
        );

        if ($response->failed()) {
            throw new RuntimeException(
                $response->json('msg')
                ?? $response->json('error_description')
                ?? 'Unable to sign in.'
            );
        }

        return $response->json();
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
