@php
    $links = [
        [
            'label' => 'Home',
            'route' => 'home',
        ],
        [
            'label' => 'Apps',
            'route' => 'apps',
        ],
        [
            'label' => 'Directory',
            'route' => 'directory',
        ],
    ];
@endphp

<nav class="navbar">
    <div class="navbar-container">
        <a href="{{ route('home') }}" class="navbar-brand">
            Elsewhere
        </a>

        <!-- <div class="navbar-links">            
            <x-nav.nav-link href="{{ route('home') }}" :active="request()->routeIs('home')">
                Home
            </x-nav.nav-link>

            <x-nav.nav-link href="{{ route('apps') }}" :active="request()->routeIs('games')">
                Apps
            </x-nav.nav-link>

            <x-nav.nav-link href="{{ route('directory') }}" :active="request()->routeIs('directory')">
                Directory
            </x-nav.nav-link>
        </div> -->

        <x-nav.navigation :links="$links"/>

        <button class="navbar-action">
            Sign in
        </button>
    </div>
</nav>