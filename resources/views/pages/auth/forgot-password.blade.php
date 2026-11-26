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

                @if(session('recovery_sent'))
                    <div class="auth-page-notice" role="status">
                        {{ session('recovery_sent') }}
                    </div>
                @else
                    @if($errors->has('recovery'))
                        <div class="auth-page-notice" role="alert">
                            {{ $errors->first('recovery') }}
                        </div>
                    @endif

                    <form
                        class="navbar-login-form"
                        method="POST"
                        action="{{ route('password.email') }}"
                    >
                        @csrf

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

                            @error('email')
                                <p role="alert">{{ $message }}</p>
                            @enderror
                        </div>

                        <button
                            type="submit"
                            class="navbar-login-submit"
                        >
                            Send reset link
                        </button>
                    </form>
                @endif

                <div class="auth-page-footer">
                    <a href="{{ route('login') }}">
                        Back to sign in
                    </a>
                </div>

            </div>

        </div>
    </section>
@endsection
