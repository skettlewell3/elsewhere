@extends('layouts.app')

@section('title', 'Create Account | Elsewhere')

@section('content')
    @php
        $registrationSuccessful = session()->has('registration_success');
        $registrationPending = session()->has('registration_pending');
        $registrationFinished = $registrationSuccessful || $registrationPending;
        $registrationEnabled = config('registration.enabled', false);
    @endphp

    <section class="auth-page">
        <div class="auth-page-container auth-register-container">

            <div class="auth-page-header">
                @if($registrationSuccessful)
                    <h1>Check your email</h1>

                    <p>
                        Your Elsewhere account has been created.
                    </p>
                @elseif($registrationPending)
                    <h1>Registration needs checking</h1>

                    <p>
                        We couldn't confirm whether your account
                        was created.
                    </p>
                @else
                    <h1>Create your Elsewhere account</h1>

                    <p>
                        One account for Elsewhere and all its
                        connected applications.
                    </p>
                @endif
            </div>

            <div class="auth-page-form">

                @if($registrationSuccessful)
                    <div class="auth-page-notice" role="status">
                        <p>
                            Your account has been created.
                            Check your email for a confirmation
                            link before signing in.
                        </p>

                        <p>
                            If the email hasn't arrived, check
                            your junk or spam folder.
                        </p>
                    </div>

                    <div class="auth-page-footer">
                        <a href="{{ route('login') }}">
                            Go to sign in
                        </a>
                    </div>

                @elseif($registrationPending)
                    <div class="auth-page-notice" role="alert">
                        <p>
                            We couldn't verify whether your
                            account was created.
                        </p>

                        <p>
                            Please don't submit another
                            registration. Your request needs
                            to be checked before you try again.
                        </p>
                    </div>

                @else

                    @if($errors->has('registration'))
                        <div class="auth-page-notice" role="alert">
                            {{ $errors->first('registration') }}
                        </div>
                    @endif

                    <form
                        class="navbar-login-form"
                        method="POST"
                        action="{{ route('register.store') }}"
                    >
                        @csrf

                        <div class="auth-register-name-row">
                            <div class="navbar-login-field">
                                <label for="register-first-name">
                                    First name
                                </label>

                                <input
                                    id="register-first-name"
                                    type="text"
                                    name="first_name"
                                    value="{{ old('first_name') }}"
                                    autocomplete="given-name"
                                    maxlength="100"
                                    placeholder="First name"
                                    required
                                    @disabled(!$registrationEnabled)
                                >

                                @error('first_name')
                                    <p role="alert">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="navbar-login-field">
                                <label for="register-last-name">
                                    Last name
                                </label>

                                <input
                                    id="register-last-name"
                                    type="text"
                                    name="last_name"
                                    value="{{ old('last_name') }}"
                                    autocomplete="family-name"
                                    maxlength="100"
                                    placeholder="Last name"
                                    required
                                    @disabled(!$registrationEnabled)
                                >

                                @error('last_name')
                                    <p role="alert">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="navbar-login-field">
                            <label for="register-email">
                                Email address
                            </label>

                            <input
                                id="register-email"
                                type="email"
                                name="email"
                                value="{{ old('email') }}"
                                autocomplete="email"
                                placeholder="you@example.com"
                                required
                                @disabled(!$registrationEnabled)
                            >

                            @error('email')
                                <p role="alert">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="navbar-login-field">
                            <label for="register-password">
                                Password
                            </label>

                            <input
                                id="register-password"
                                type="password"
                                name="password"
                                autocomplete="new-password"
                                minlength="8"
                                maxlength="128"
                                aria-describedby="register-password-help"
                                placeholder="Create a password"
                                required
                                @disabled(!$registrationEnabled)
                            >

                            <p id="register-password-help">
                                Minimum 8 characters.
                                Maximum 128 characters.
                            </p>

                            @error('password')
                                <p role="alert">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="navbar-login-field">
                            <label for="register-password-confirmation">
                                Confirm password
                            </label>

                            <input
                                id="register-password-confirmation"
                                type="password"
                                name="password_confirmation"
                                autocomplete="new-password"
                                minlength="8"
                                maxlength="128"
                                placeholder="Confirm your password"
                                required
                                @disabled(!$registrationEnabled)
                            >
                        </div>

                        <div class="navbar-login-field">
                            <label for="register-invite-code">
                                Sign-up code
                            </label>

                            <input
                                id="register-invite-code"
                                type="text"
                                name="invite_code"
                                autocomplete="off"
                                maxlength="128"
                                placeholder="Enter your sign-up code"
                                required
                                @disabled(!$registrationEnabled)
                            >

                            @error('invite_code')
                                <p role="alert">{{ $message }}</p>
                            @enderror
                        </div>

                        <button
                            type="submit"
                            class="navbar-login-submit"
                            @disabled(!$registrationEnabled)
                        >
                            Create account
                        </button>
                    </form>

                    @unless($registrationEnabled)
                        <div class="auth-page-notice" role="status">
                            Registration is not available yet.
                            Account creation and sign-up code
                            verification will be enabled once
                            the registration service is ready.
                        </div>
                    @endunless

                    <div class="auth-page-footer">
                        Already have an account?
                        <a href="{{ route('login') }}">
                            Sign in
                        </a>
                    </div>

                @endif

            </div>
        </div>
    </section>
@endsection
