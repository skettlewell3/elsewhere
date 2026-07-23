<nav class="navbar">
    <div class="navbar-container">
        <a href="{{ route('home') }}" class="navbar-brand">
            Elsewhere
        </a>

        <div class="navbar-links">
            <span class="nav-active-indicator"></span>

            <x-nav.nav-link href="{{ route('home') }}" :active="request()->routeIs('home')">
                Home
            </x-nav.nav-link>

            <x-nav.nav-link href="{{ route('games') }}" :active="request()->routeIs('games')">
                Apps
            </x-nav.nav-link>

            <x-nav.nav-link href="{{ route('directory') }}" :active="request()->routeIs('directory')">
                Directory
            </x-nav.nav-link>
        </div>

        <button class="navbar-action">
            Sign in
        </button>
    </div>
</nav>