@extends('layouts.app')

@section('title', 'Sign in | Elsewhere')

@section('content')
    <section class="auth-page">
        <div class="auth-page-container">

            <div class="auth-page-header">
                <h1>Sign in to Elsewhere</h1>

                <p>
                    Access your Elsewhere account and
                    connected applications.
                </p>
            </div>

            @if(session('registration_success'))
                <div class="auth-page-notice" role="status">
                    {{ session('registration_success') }}
                </div>
            @endif

            @if(session('password_reset_success'))
                <div class="auth-page-notice" role="status">
                    {{ session('password_reset_success') }}
                </div>
            @endif

            <div class="auth-page-form">
                <x-auth.login-form id-prefix="page-login" />
            </div>

        </div>
    </section>
@endsection
