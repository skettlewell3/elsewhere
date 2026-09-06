@php
    $favourites = config('apps');
@endphp

<section
    class="dashboard-favourites"
    data-favourites-gallery
>

    <button
        class="favourites-nav favourites-nav-previous"
        type="button"
        aria-label="Previous favourites"
        data-favourites-previous
    >
        ‹
    </button>

    <div class="favourites-viewport">

        <div
            class="favourites-track"
            data-favourites-track
        >

            @foreach($favourites as $app)

                <div class="favourites-item">

                    <x-dashboard.favourite-card
                        :name="$app['name']"
                        :image="$app['image']"
                        :alt="$app['alt']"
                        type="app"
                        :category="$app['category']"
                        :url="$app['play_url']"
                    />

                </div>

            @endforeach

        </div>

    </div>

    <button
        class="favourites-nav favourites-nav-next"
        type="button"
        aria-label="Next favourites"
        data-favourites-next
    >
        ›
    </button>

</section>