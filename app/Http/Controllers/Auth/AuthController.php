<?php

namespace App\Http\Controllers\Auth;

use App\Exceptions\SupabaseLoginException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\AccountUser;
use App\Services\Auth\SupabaseAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function login(
        LoginRequest $request,
        SupabaseAuthService $authService
    ): RedirectResponse {
        try {
            $result = $authService->signIn(
                $request->string('email')->toString(),
                $request->string('password')->toString(),
            );
        } catch (SupabaseLoginException $exception) {
            if (in_array($exception->reason, [
                'unavailable',
                'unexpected',
            ], true)) {
                Log::warning('Supabase login failed', [
                    'reason' => $exception->reason,
                ]);
            }

            $message = match ($exception->reason) {
                'invalid_credentials' =>
                    'The email or password is incorrect.',

                'email_not_confirmed' =>
                    'Please verify your email address before signing in.',

                'rate_limited' =>
                    'Too many login attempts. Please try again later.',

                'unavailable' =>
                    'The login service is temporarily unavailable. Please try again later.',

                default =>
                    'Unable to sign in right now. Please try again later.',
            };

            return back()
                ->withErrors(['email' => $message], 'login')
                ->withInput($request->only('email'));
        }

        $authId = $result['user']['id'];

        $accountUser = AccountUser::query()
            ->where('auth_id', $authId)
            ->first();

        if (! $accountUser) {
            Log::warning(
                'Authenticated Supabase user has no Elsewhere account'
            );

            try {
                $authService->signOut(
                    $result['access_token']
                );
            } catch (\Throwable $exception) {
                report($exception);
            }

            return back()
                ->withErrors([
                    'email' => 'Unable to access your Elsewhere account. Please contact support.',
                ], 'login')
                ->withInput($request->only('email'));
        }

        Auth::guard('web')->login($accountUser);

        $request->session()->regenerate();

        $request->session()->forget([
            'auth_user_id',
            'account_id',
        ]);

        $request->session()->put([
            'supabase_access_token' => $result['access_token'],
            'supabase_refresh_token' => $result['refresh_token'],
            'supabase_expires_at' => $result['expires_at'],
        ]);

        return redirect()->intended('/');
    }

    public function logout(
        Request $request,
        SupabaseAuthService $authService
    ): RedirectResponse {
        $accessToken = $request->session()
            ->get('supabase_access_token');

        if ($accessToken) {
            try {
                $authService->signOut($accessToken);
            } catch (\Throwable $exception) {
                report($exception);
            }
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
