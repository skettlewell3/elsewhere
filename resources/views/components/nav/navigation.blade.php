@props([
    'links'
])

@php
    $current = collect($links)
        ->first(fn ($link) => request()->routeIs($link['route']));

    $others = collect($links)
        ->reject(fn ($link) => request()->routeIs($link['route']));
@endphp

<div class="navbar-navigation">
    {{-- Desktop --}}
    <div class="navbar-links navbar-links-desktop">
        @foreach($links as $link)
            <a
                href="{{ route($link['route']) }}"
                data-name="{{ strtolower($link['label']) }}"
                class="navlink {{ request()->routeIs($link['route']) ? 'active' : '' }}"
            >
                {{ $link['label'] }}
            </a>
        @endforeach
    </div>

    {{-- Mobile --}}
    <div class="navbar-links-mobile">
        <div class="mobile-nav-wrapper">
            <button
                class="navbar-mobile-current"
                type="button"
            >
                <span>
                    {{ $current['label'] }}
                </span>
    
                <span class="nav-mobile-arrow">
                    ▼
                </span>
            </button>
        </div>

        <div class="navbar-mobile-options">
            @foreach($others as $link)
                <a
                    href="{{ route($link['route']) }}"
                    class="navbar-mobile-link"
                >
                    {{ $link['label'] }}
                </a>
            @endforeach
        </div>
    </div>
</div>