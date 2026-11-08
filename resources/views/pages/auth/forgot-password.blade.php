@extends('layouts.app')

@section('title', 'Forgot Password | Elsewhere')

@section('content')
    <section class="auth-page">
        <div class="auth-page-container">

            <div class="auth-page-header">
                <h1>Forgot your password?</h1>

                <p>
                    Enter the email address associated with your
                    Elsewhere account. We'll send you a link
                    to reset your password.
                </p>
            </div>

            <div class="auth-page-form">

                <form class="navbar-login-form">
                    <div class="navbar-login-field">
                        <label for="recovery-email">
                            Email address
                        </label>

                        <input
                            id="recovery-email"
                            type="email"
                            name="email"
                            value="{{ old('email') }}"
                            autocomplete="email"
                            placeholder="you@example.com"
                            required
                        >
                    </div>

                    <button
                        type="button"
                        class="navbar-login-submit"
                        disabled
                    >
                        Send reset link
                    </button>
                </form>

                <div class="auth-page-notice" role="status">
                    Password recovery is not available yet.
                    This feature will be enabled soon.
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
