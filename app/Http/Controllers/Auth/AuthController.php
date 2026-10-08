<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\AccountUser;
use App\Services\Auth\SupabaseAuthService;
use Illuminate\Http\RedirectResponse;

class AuthController extends Controller
{
    public function login(
        LoginRequest $request,
        SupabaseAuthService $authService
    ): RedirectResponse {
        $result = $authService->signIn(
            $request->string('email')->toString(),
            $request->string('password')->toString(),
        );

        $authId = $result['user']['id'];

        $accountUser = AccountUser::query()
            ->where('auth_id', $authId)
            ->firstOrFail();

        $request->session()->regenerate();

        $request->session()->put([
            'auth_user_id' => $authId,
            'account_id' => $accountUser->account_id,
            'supabase_access_token' => $result['access_token'],
            'supabase_refresh_token' => $result['refresh_token'],
            'supabase_expires_at' => $result['expires_at'],
        ]);

        return redirect()->intended('/');
    }
}
