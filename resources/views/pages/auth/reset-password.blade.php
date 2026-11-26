@extends('layouts.app')

@section('title', 'Reset Password | Elsewhere')

@section('content')
    <section class="auth-page">
        <div class="auth-page-container">

            <div class="auth-page-header">
                <h1>Reset your password</h1>

                <p>
                    Choose a new password for your Elsewhere account.
                    Your new password will be used to sign in to
                    Elsewhere and its connected applications.
                </p>
            </div>

            <div class="auth-page-form">

                @if(!$validLink)
                    <div class="auth-page-notice" role="alert">
                        This password reset link is missing or invalid.
                        Please request a new one.
                    </div>

                    <div class="auth-page-footer">
                        <a href="{{ route('password.request') }}">
                            Request another reset link
                        </a>
                    </div>
                @else

                    @if($errors->any())
                        <div class="auth-page-notice" role="alert">
                            Please check the information below
                            and try again.
                        </div>
                    @endif

                    <form
                        class="navbar-login-form"
                        method="POST"
                        action="{{ route('password.update') }}"
                    >
                        @csrf

                        <input
                            type="hidden"
                            name="token_hash"
                            value="{{ $tokenHash }}"
                        >

                        <input
                            type="hidden"
                            name="type"
                            value="recovery"
                        >

                        <div class="navbar-login-field">
                            <label for="reset-password">
                                New password
                            </label>

                            <input
                                id="reset-password"
                                type="password"
                                name="password"
                                autocomplete="new-password"
                                minlength="8"
                                maxlength="128"
                                aria-describedby="reset-password-help"
                                placeholder="Enter new password"
                                required
                            >

                            <p id="reset-password-help">
                                Minimum 8 characters.
                                Maximum 128 characters.
                            </p>

                            @error('password')
                                <p role="alert">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="navbar-login-field">
                            <label for="reset-password-confirmation">
                                Confirm new password
                            </label>

                            <input
                                id="reset-password-confirmation"
                                type="password"
                                name="password_confirmation"
                                autocomplete="new-password"
                                minlength="8"
                                maxlength="128"
                                placeholder="Confirm new password"
                                required
                            >
                        </div>

                        <button
                            type="submit"
                            class="navbar-login-submit"
                        >
                            Reset password
                        </button>
                    </form>

                    <div class="auth-page-footer">
                        <a href="{{ route('login') }}">
                            Back to sign in
                        </a>
                    </div>

                @endif

            </div>

        </div>
    </section>
@endsection
