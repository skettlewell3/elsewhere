
@extends('layouts.app')

@section('title', 'Create Account | Elsewhere')

@section('content')
    <section class="auth-page">
        <div class="auth-page-container auth-register-container">

            <div class="auth-page-header">
                <h1>Create your Elsewhere account</h1>

                <p>
                    One account for Elsewhere and all its
                    connected applications.
                </p>
            </div>

            <div class="auth-page-form">
                <form class="navbar-login-form">
                    
                    <div class="auth-register-name-row">
                        <div class="navbar-login-field">
                            <label for="register-first-name">
                                First name
                            </label>

                            <input
                                id="register-first-name"
                                type="text"
                                name="first_name"
                                autocomplete="given-name"
                                maxlength="100"
                                placeholder="First name"
                                required
                            >
                        </div>

                        <div class="navbar-login-field">
                            <label for="register-last-name">
                                Last name
                            </label>

                            <input
                                id="register-last-name"
                                type="text"
                                name="last_name"
                                autocomplete="family-name"
                                maxlength="100"
                                placeholder="Last name"
                                required
                            >
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
                        >
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
                            placeholder="Create a password"
                            required
                        >
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
                            placeholder="Confirm your password"
                            required
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
                            placeholder="Enter your sign-up code"
                            required
                        >
                    </div>

                    <button
                        type="button"
                        class="navbar-login-submit"
                        disabled
                    >
                        Create account
                    </button>

                </form>

                <div class="auth-page-notice" role="status">
                    Registration is not available yet.
                    Account creation and sign-up code
                    verification will be enabled once
                    the registration service is ready.
                </div>

                <div class="auth-page-footer">
                    Already have an account?
                    <a href="{{ route('login') }}">
                        Sign in
                    </a>
                </div>

            </div>

        </div>
    </section>
@endsection
