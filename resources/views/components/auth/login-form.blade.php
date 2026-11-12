
@props([
    'idPrefix' => 'login',
])

<form
    method="POST"
    action="{{ route('login.submit') }}"
    class="navbar-login-form"
>
    @csrf

    @if ($errors->login->has('email') || $errors->login->has('password'))
        <div
            class="navbar-login-error"
            role="alert"
        >
            {{ $errors->login->first('email') ?: $errors->login->first('password') }}
        </div>
    @endif

    <div class="navbar-login-field">
        <label for="{{ $idPrefix }}-email">Email</label>

        <input
            id="{{ $idPrefix }}-email"
            type="email"
            name="email"
            value="{{ old('email') }}"
            autocomplete="email"
            aria-invalid="{{ $errors->login->has('email') ? 'true' : 'false' }}"
            required
        >
    </div>

    <div class="navbar-login-field">
        <label for="{{ $idPrefix }}-password">Password</label>

        <input
            id="{{ $idPrefix }}-password"
            type="password"
            name="password"
            autocomplete="current-password"
            aria-invalid="{{ $errors->login->has('password') ? 'true' : 'false' }}"
            required
        >
    </div>

    <button
        type="submit"
        class="navbar-login-submit"
    >
        Sign in
    </button>

    <div class="navbar-login-links">
        <a href="{{ route('password.request') }}">
            Forgot password?
        </a>

        <a href="#">
            Create account
        </a>
    </div>
</form>
