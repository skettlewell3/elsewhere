<?php

namespace App\Services\Auth;

use App\Models\Account;
use App\Models\AccountUser;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

class CurrentAccountService
{
    public function __construct(
        private readonly SupabaseAuthService $authService
    ) {}

    public function authId(): ?string
    {
        return Auth::guard('web')->id();
    }

    public function accountUser(): ?AccountUser
    {
        $user = Auth::guard('web')->user();

        return $user instanceof AccountUser
            ? $user
            : null;
    }

    public function account(): ?Account
    {
        return $this->accountUser()?->account;
    }

    public function check(): bool
    {
        return Auth::guard('web')->check();
    }

    public function ensureFreshSession(): bool
    {
        $authId = $this->authId();

        $refreshToken = session('supabase_refresh_token');
        $expiresAt = session('supabase_expires_at');

        if (! $authId || ! $refreshToken || ! $expiresAt) {
            return false;
        }

        if ((int) $expiresAt > now()->timestamp + 300) {
            return true;
        }

        try {
            $result = $this->authService->refreshSession(
                $refreshToken
            );
        } catch (RuntimeException $exception) {
            report($exception);

            return false;
        }

        if (($result['user']['id'] ?? null) !== $authId) {
            return false;
        }

        if (
            empty($result['access_token']) ||
            empty($result['refresh_token']) ||
            empty($result['expires_at'])
        ) {
            return false;
        }

        session([
            'supabase_access_token' => $result['access_token'],
            'supabase_refresh_token' => $result['refresh_token'],
            'supabase_expires_at' => $result['expires_at'],
        ]);

        return true;
    }
}
