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

                <form class="navbar-login-form">

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
                            placeholder="Enter new password"
                            required
                            disabled
                        >
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
                            placeholder="Confirm new password"
                            required
                            disabled
                        >
                    </div>

                    <button
                        type="button"
                        class="navbar-login-submit"
                        disabled
                    >
                        Reset password
                    </button>

                </form>

                <div class="auth-page-notice" role="status">
                    Password reset is not available yet.
                    This feature will be enabled when
                    secure recovery-link verification is implemented.
                </div>

                <div class="auth-page-footer">
                    <a href="{{ route('login') }}">
                        Back to sign in
                    </a>
                </div>

            </div>

        </div>
    </section>
@endsection
