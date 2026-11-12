
@props(['app'])

<div class="app-card">
    <div class="app-card-header">
        <span class="app-category">
            {{ $app->category }}
        </span>

        <div class="app-favourite-bg">
            <button class="app-favourite" type="button">
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

        <p>
            {{ $app->description }}
        </p>
    </div>

    <div class="app-card-actions">
        <a href="{{ $app->info_url ?? '#' }}">
            Learn More
        </a>

        <a href="{{ $app->launch_url ?? '#' }}">
            Play
        </a>
    </div>
</div>
