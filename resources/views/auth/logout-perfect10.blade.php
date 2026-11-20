@extends('layouts.app')

@section('title', 'Sign out | Elsewhere')

@section('content')
    <section class="auth-page">
        <div class="auth-page-container">
            <div class="auth-page-header">
                <h1>Sign out of Elsewhere?</h1>

                <p>
                    This will sign you out of Elsewhere and Perfect10.
                    You can sign back in to return to Perfect10.
                </p>
            </div>

            <div class="auth-page-form">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <input
                        type="hidden"
                        name="return_app"
                        value="perfect10"
                    >

                    <button
                        type="submit"
                        class="navbar-login-submit"
                    >
                        Sign out
                    </button>
                </form>

                <a
                    href="{{ config('elsewhere_oauth.perfect10_url') }}"
                    class="auth-logout-cancel"
                >
                    Cancel and return to Perfect10
                </a>

            </div>
        </div>
    </section>
@endsection
