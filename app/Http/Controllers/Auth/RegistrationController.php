<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\RegistrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Throwable;

class RegistrationController extends Controller
{
    public function store(
        Request $request,
        RegistrationService $registration
    ): RedirectResponse {
        abort_unless(
            config('registration.enabled', false),
            503,
            'Registration is not currently available.'
        );

        $validated = $request->validate([
            'first_name' => [
                'required',
                'string',
                'max:100',
            ],
            'last_name' => [
                'required',
                'string',
                'max:100',
            ],
            'email' => [
                'required',
                'string',
                'email',
                'max:254',
            ],
            'password' => [
                'required',
                'string',
                'min:8',
                'max:128',
                'confirmed',
            ],
            'invite_code' => [
                'required',
                'string',
                'max:128',
            ],
        ]);

        try {
            $result = $registration->register(
                $validated['first_name'],
                $validated['last_name'],
                $validated['email'],
                $validated['password'],
                $validated['invite_code']
            );
        } catch (Throwable $exception) {
            report($exception);

            return back()
                ->withErrors([
                    'registration' => 'Registration is temporarily unavailable. Please contact support before trying again.',
                ])
                ->withInput(
                    $request->only(
                        'first_name',
                        'last_name',
                        'email'
                    )
                );
        }

        $status = $result['status'] ?? null;

        if ($status === 'completed') {
            return redirect()
                ->route('login')
                ->with(
                    'registration_success',
                    'Your account has been created. Please confirm your email address before signing in.'
                );
        }

        if ($status === 'needs_reconciliation') {
            return redirect()
                ->route('register')
                ->with(
                    'registration_pending',
                    'Your registration request needs verification. Do not submit another registration. Please contact support if you need assistance.'
                );
        }

        if (
            $status === 'rejected' &&
            ($result['error_code'] ?? null) ===
                'invalid_signup_code'
        ) {
            return back()
                ->withErrors([
                    'invite_code' => 'This sign-up code is invalid, expired or has no remaining uses.',
                ])
                ->withInput(
                    $request->only(
                        'first_name',
                        'last_name',
                        'email'
                    )
                );
        }

        return back()
            ->withErrors([
                'registration' => 'Registration could not be completed. Please contact support before trying again.',
            ])
            ->withInput(
                $request->only(
                    'first_name',
                    'last_name',
                    'email'
                )
            );
    }
}
