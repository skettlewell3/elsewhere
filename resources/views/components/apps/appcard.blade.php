@props([
    'app',
    'isLoggedIn' => false,
    'membership' => null,
])

@php
    $appAvailable = $app->status === 'active';

    $membershipStatus = $membership?->status;

    $launchUrl = route('apps.launch', [
        'app' => $app->slug,
    ]);

    if (! $appAvailable) {
        $actionLabel = match ($app->status) {
            'coming_soon' => 'Coming Soon',
            'maintenance' => 'Unavailable',
            default => 'Unavailable',
        };

        $actionUrl = null;
    } elseif (! $isLoggedIn) {
        $actionLabel = 'Log in to Play';
        $actionUrl = $launchUrl;
    } else {
        [$actionLabel, $actionUrl] = match ($membershipStatus) {
            'active' => [
                'Play',
                $launchUrl,
            ],

            'pending' => [
                'Registration Pending',
                null,
            ],

            'suspended' => [
                'Access Suspended',
                null,
            ],

            default => [
                'Create Profile',
                $launchUrl,
            ],
        };
    }
@endphp

<div class="app-card">
    <div class="app-card-header">
        <span class="app-category">
            {{ $app->category }}
        </span>

        <div class="app-favourite-bg">
            <button
                class="app-favourite"
                type="button"
            >
                ★
            </button>
        </div>
    </div>

    <div class="app-card-media">
        <img
            src="{{ asset($app->image) }}"
            alt="{{ $app->alt ?? $app->name }}"
        >
    </div>

    <div class="app-card-content">
        <h2>{{ $app->name }}</h2>

        <p>{{ $app->description }}</p>
    </div>

    <div class="app-card-actions">
        @if($app->info_url)
            <a href="{{ $app->info_url }}">
                Learn More
            </a>
        @else
            <span>Learn More</span>
        @endif

        @if($actionUrl)
            <a href="{{ $actionUrl }}">
                {{ $actionLabel }}
            </a>
        @else
            <span class="app-action-disabled">
                {{ $actionLabel }}
            </span>
        @endif
    </div>
</div>
