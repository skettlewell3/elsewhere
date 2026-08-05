@props(['app'])

<div class="app-card">
    <div class="app-card-header">
        <span class="app-category">Sports</span>

        <div class="app-favourite-bg">
            <button class="app-favourite">
                ★
            </button>
        </div>
    </div>

    <div class="app-card-media">
        <img src="{{ asset($app['image']) }}" alt="{{ asset($app['alt']) }}">
    </div>

    <div class="app-card-content">
        <h2>{{ $app['name'] }}</h2>
    
        <p>
            {{ $app['description'] }}
        </p>
    </div>


    <div class="app-card-actions">
        <a href="">Learn More</a>

        <a href="">Play</a>
    </div>
</div>