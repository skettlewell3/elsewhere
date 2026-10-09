<?php

namespace App\Services\Auth;

use App\Models\Account;
use App\Models\AccountUser;
use RuntimeException;

class CurrentAccountService
{
    public function __construct(
        private readonly SupabaseAuthService $authService
    ) {}

    public function authId(): ?string
    {
        return session('auth_user_id');
    }

    public function accountUser(): ?AccountUser
    {
        $authId = $this->authId();

        if (! $authId) {
            return null;
        }

        return AccountUser::query()
            ->with('account')
            ->where('auth_id', $authId)
            ->first();
    }

    public function account(): ?Account
    {
        return $this->accountUser()?->account;
    }

    public function check(): bool
    {
        return $this->accountUser() !== null;
    }

    public function ensureFreshSession(): bool
    {
        $authId = session('auth_user_id');
        $refreshToken = session('supabase_refresh_token');
        $expiresAt = session('supabase_expires_at');

        if (! $authId || ! $refreshToken || ! $expiresAt) {
            return false;
        }

        /*
         * Keep using the current access token while it has more
         * than five minutes remaining.
         */
        if ((int) $expiresAt > now()->timestamp + 300) {
            return true;
        }

        try {
            $result = $this->authService->refreshSession($refreshToken);
        } catch (RuntimeException $exception) {
            $this->clearSession();

            report($exception);

            return false;
        }

        /*
         * The refreshed session must still belong to the same
         * authenticated user.
         */
        if (($result['user']['id'] ?? null) !== $authId) {
            $this->clearSession();

            return false;
        }

        session([
            'supabase_access_token' => $result['access_token'],
            'supabase_refresh_token' => $result['refresh_token'],
            'supabase_expires_at' => $result['expires_at'],
        ]);

        return true;
    }

    private function clearSession(): void
    {
        session()->forget([
            'auth_user_id',
            'account_id',
            'supabase_access_token',
            'supabase_refresh_token',
            'supabase_expires_at',
        ]);
    }
}
