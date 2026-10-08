<form
    method="POST"
    action="{{ route('login') }}"
    class="navbar-login-form"
>
    @csrf

    <div class="navbar-login-field">
        <label for="login-email">Email</label>

        <input
            id="login-email"
            type="email"
            name="email"
            value="{{ old('email') }}"
            autocomplete="email"
            required
        >
    </div>

    <div class="navbar-login-field">
        <label for="login-password">Password</label>

        <input
            id="login-password"
            type="password"
            name="password"
            autocomplete="current-password"
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
        <a href="#">
            Forgot password?
        </a>

        <a href="#">
            Create account
        </a>
    </div>
</form>