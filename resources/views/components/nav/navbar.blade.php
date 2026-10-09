@php
    use App\Models\AccountUser;

    $links = [
        [
            'label' => 'Directory',
            'route' => 'directory',
        ],
        [
            'label' => 'Dashboard',
            'route' => 'dashboard',
        ],
        [
            'label' => 'Discover',
            'route' => 'discover',
        ],
    ];

    $accountUser = null;

    if (session()->has('auth_user_id')) {
        $accountUser = AccountUser::query()
            ->where('auth_id', session('auth_user_id'))
            ->first();
    }

    $firstName = $accountUser?->first_name;
    $fullName = $accountUser
        ? trim(($accountUser->first_name ?? '') . ' ' . ($accountUser->last_name ?? ''))
        : null;
@endphp

<nav class="navbar">
    <div class="navbar-container">

        <a href="{{ route('directory') }}" class="navbar-brand">
            Elsewhere
        </a>

        <x-nav.navigation :links="$links"/>

        <div class="navbar-actions">
            <x-theme.menu />

            @if ($accountUser)
                <details class="navbar-account">
                    <summary class="navbar-account-trigger">

                        <span class="navbar-account-avatar" aria-hidden="true">
                            <svg
                                viewBox="0 0 24 24"
                                width="20"
                                height="20"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >
                                <circle cx="12" cy="8" r="4" />
                                <path d="M4 20c0-4 3.6-7 8-7s8 3 8 7" />
                            </svg>
                        </span>

                        <span class="navbar-account-name">
                            @if ($firstName)
                                Hello, {{ $firstName }}!
                            @else
                                My account
                            @endif
                        </span>

                    </summary>

                    <div class="navbar-account-panel">

                        @if ($fullName)
                            <div class="navbar-account-full-name">
                                {{ $fullName }}
                            </div>
                        @endif

                        <div class="navbar-account-username">
                            {{ $accountUser->username }}
                        </div>

                        @if ($accountUser->is_founding_member)
                            <div class="navbar-account-badge">
                                Founding Member
                            </div>
                        @endif

                        <form
                            method="POST"
                            action="{{ route('logout') }}"
                            class="navbar-account-logout"
                        >
                            @csrf

                            <button type="submit">
                                Log out
                            </button>
                        </form>

                    </div>
                </details>
            @else
                <details class="navbar-login">
                    <summary class="navbar-action">
                        Sign in
                    </summary>

                    <div class="navbar-login-panel">
                        <x-auth.login-form />
                    </div>
                </details>
            @endif
        </div>

        <details class="navbar-menu">
            <summary
                class="navbar-menu-trigger"
                aria-label="Open menu"
            >
                ☰
            </summary>

            <div class="navbar-menu-content">

                <div class="navbar-menu-section navbar-menu-auth">

                    @if ($accountUser)
                        <div class="navbar-mobile-account">

                            <div class="navbar-account-trigger">

                                <span class="navbar-account-avatar" aria-hidden="true">
                                    <svg
                                        viewBox="0 0 24 24"
                                        width="20"
                                        height="20"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.8"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    >
                                        <circle cx="12" cy="8" r="4" />
                                        <path d="M4 20c0-4 3.6-7 8-7s8 3 8 7" />
                                    </svg>
                                </span>

                                <span class="navbar-account-name">
                                    @if ($firstName)
                                        Hello, {{ $firstName }}!
                                    @else
                                        My account
                                    @endif
                                </span>

                            </div>

                            <div class="navbar-account-username">
                                {{ $accountUser->username }}
                            </div>

                            @if ($accountUser->is_founding_member)
                                <div class="navbar-account-badge">
                                    Founding Member
                                </div>
                            @endif

                            <form
                                method="POST"
                                action="{{ route('logout') }}"
                                class="navbar-account-logout"
                            >
                                @csrf

                                <button type="submit">
                                    Log out
                                </button>
                            </form>

                        </div>
                    @else
                        <x-auth.login-form />
                    @endif

                </div>

                <div class="navbar-menu-section">
                    <x-theme.mode-toggle />
                </div>

                <div class="navbar-menu-section">
                    <x-theme.selector />
                </div>

            </div>
        </details>

    </div>
</nav>