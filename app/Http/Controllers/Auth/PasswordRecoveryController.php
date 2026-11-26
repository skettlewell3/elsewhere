<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\SupabaseAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use RuntimeException;

class PasswordRecoveryController extends Controller
{
    public function __construct(
        private readonly SupabaseAuthService $authService
    ) {}

    public function requestForm(): View
    {
        return view('pages.auth.forgot-password');
    }

    public function requestReset(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:254'],
        ]);

        try {
            $this->authService->requestPasswordReset(
                strtolower(trim($validated['email'])),
                route('password.reset')
            );
        } catch (RuntimeException $exception) {
            report($exception);

            return back()->withErrors([
                'recovery' => 'Password recovery is temporarily unavailable. Please try again later.',
            ]);
        }

        return back()->with(
            'recovery_sent',
            'If an account exists for that email address, a password reset link has been sent.'
        );
    }

    public function resetForm(Request $request): Response
    {
        $tokenHash = $request->query('token_hash');
        $type = $request->query('type');

        $validLink = is_string($tokenHash)
            && preg_match('/\A[a-fA-F0-9]{32,128}\z/D', $tokenHash) === 1
            && $type === 'recovery';

        return response()->view('pages.auth.reset-password', [
            'tokenHash' => $validLink ? $tokenHash : null,
            'validLink' => $validLink,
        ])->withHeaders([
            'Cache-Control' => 'no-store, private',
            'Referrer-Policy' => 'no-referrer',
        ]);
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $validated = Validator::make($request->all(), [
            'token_hash' => [
                'required',
                'string',
                'regex:/\A[a-fA-F0-9]{32,128}\z/D',
            ],
            'type' => ['required', 'in:recovery'],
            'password' => [
                'required',
                'string',
                'min:8',
                'max:128',
                'confirmed',
            ],
        ])->validate();

        try {
            $accessToken = $this->authService->verifyRecoveryToken(
                $validated['token_hash']
            );

            $this->authService->updatePassword(
                $accessToken,
                $validated['password']
            );
        } catch (RuntimeException $exception) {
            report($exception);

            return redirect()
                ->route('password.request')
                ->withErrors([
                    'recovery' => 'The reset link could not be used. Please request a new password reset email.',
                ]);
        }

        $request->session()->regenerateToken();

        return redirect()
            ->route('login')
            ->with(
                'password_reset_success',
                'Your password has been updated. You can now sign in.'
            );
    }
}
