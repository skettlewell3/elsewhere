@php
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
@endphp

<nav class="navbar">
    <div class="navbar-container">

        <a href="{{ route('directory') }}" class="navbar-brand">
            Elsewhere
        </a>

        <x-nav.navigation :links="$links"/>

        <div class="navbar-actions">
            <x-theme.menu />

            <button class="navbar-action">
                Sign in
            </button>
        </div>

        <details class="navbar-menu">
            <summary
                class="navbar-menu-trigger"
                aria-label="Open menu"
            >
                ☰
            </summary>

            <div class="navbar-menu-content">

                <div class="navbar-menu-section">
                    <button class="navbar-menu-signin">
                        Sign in
                    </button>
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