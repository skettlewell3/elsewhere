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
}
