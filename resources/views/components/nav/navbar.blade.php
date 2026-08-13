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

        <button class="navbar-action">
            Sign in
        </button>
    </div>
</nav>