@php
    $cards = [
        [
            'name' => 'Coffee House',
            'slug' => 'coffee-house',
            'subtitle' => 'Rewards',
            'type' => 'loyalty',
            'background' => '#8B4513',
            'foreground' => '#ffffff',
        ],
        [
            'name' => 'Southampton Film Club',
            'slug' => 'southampton-film-club',
            'subtitle' => 'Member',
            'type' => 'membership',
            'background' => '#173B6C',
            'foreground' => '#ffffff',
        ],
        [
            'name' => 'The Crown',
            'slug' => 'the-crown',
            'subtitle' => 'Rewards',
            'type' => 'loyalty',
            'background' => '#17633D',
            'foreground' => '#ffffff',
        ],
        [
            'name' => 'City Gym',
            'slug' => 'city-gym',
            'subtitle' => 'Gold Member',
            'type' => 'membership',
            'background' => '#632E79',
            'foreground' => '#ffffff',
        ],
        [
            'name' => 'Local Coffee Co.',
            'slug' => 'local-coffee-co',
            'subtitle' => 'Rewards',
            'type' => 'loyalty',
            'background' => '#B85C16',
            'foreground' => '#ffffff',
        ],
        [
            'name' => 'Harbour Club',
            'slug' => 'harbour-club',
            'subtitle' => 'Member',
            'type' => 'membership',
            'background' => '#126E82',
            'foreground' => '#ffffff',
        ],
        [
            'name' => 'The Bakery',
            'slug' => 'the-bakery',
            'subtitle' => 'Rewards',
            'type' => 'loyalty',
            'background' => '#A9364B',
            'foreground' => '#ffffff',
        ],
        [
            'name' => 'City Arts',
            'slug' => 'city-arts',
            'subtitle' => 'Member',
            'type' => 'membership',
            'background' => '#8A6A17',
            'foreground' => '#ffffff',
        ],
        [
            'name' => 'Dockside Social',
            'slug' => 'dockside-social',
            'subtitle' => 'Rewards',
            'type' => 'loyalty',
            'background' => '#3949AB',
            'foreground' => '#ffffff',
        ],
        [
            'name' => 'South Coast Fitness',
            'slug' => 'south-coast-fitness',
            'subtitle' => 'Member',
            'type' => 'membership',
            'background' => '#9A3412',
            'foreground' => '#ffffff',
        ],
    ];
@endphp

<section
    class="dashboard-card-holder"
    data-card-holder
>
    <header class="dashboard-card-holder__header">
        <div>
            <h2 class="dashboard-card-holder__title">
                My Cards
            </h2>

            <p class="dashboard-card-holder__meta">
                {{ count($cards) }} cards
            </p>
        </div>

        <button
            type="button"
            class="dashboard-card-holder__expand"
            data-card-holder-toggle
            aria-expanded="false"
        >
            <span data-card-holder-toggle-label>
                Show all
            </span>

            <span
                class="dashboard-card-holder__expand-icon"
                data-card-holder-toggle-icon
                aria-hidden="true"
            >
                ↓
            </span>
        </button>
    </header>

    <div
        class="dashboard-card-holder__tools"
        data-card-holder-tools
        hidden
    >
        <label class="dashboard-card-holder__search">
            <span class="sr-only">
                Search cards
            </span>

            <input
                type="search"
                placeholder="Search cards"
                autocomplete="off"
                data-card-holder-search
            >
        </label>

        <div
            class="dashboard-card-holder__filters"
            data-card-holder-filters
        >
            <button
                type="button"
                class="is-active"
                data-card-filter="all"
            >
                All
            </button>

            <button
                type="button"
                data-card-filter="loyalty"
            >
                Rewards
            </button>

            <button
                type="button"
                data-card-filter="membership"
            >
                Membership
            </button>
        </div>
    </div>

    <div
        class="dashboard-card-holder__viewport"
        data-card-holder-viewport
    >
        <div
            class="dashboard-card-holder__pages"
            data-card-holder-pages
        ></div>
    </div>

    <div
        class="dashboard-card-holder__pagination"
        data-card-holder-pagination
        aria-label="Card pages"
    ></div>

    <p
        class="dashboard-card-holder__empty"
        data-card-holder-empty
        hidden
    >
        No cards found.
    </p>

    <div
        class="dashboard-card-holder__source"
        data-card-holder-source
        hidden
    >
        @foreach ($cards as $card)
            <x-cards.card
                :name="$card['name']"
                :subtitle="$card['subtitle']"
                :type="$card['type']"
                :background="$card['background']"
                :foreground="$card['foreground']"
                :href="route('cards.show', $card['slug'])"
                :info-href="route('cards.info', $card['slug'])"
            />
        @endforeach
    </div>

    <footer class="dashboard-card-holder__footer">
        <a href="{{ route('cards.index') }}">
            Open card holder
        </a>
    </footer>
</section>