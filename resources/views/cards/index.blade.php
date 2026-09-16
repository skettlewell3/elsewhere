@extends('layouts.app')

@section('content')

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
    ];
@endphp

<div
    class="card-holder-page"
    data-full-card-holder
>
    <header class="card-holder-page__header">
        <a href="{{ route('dashboard') }}">
            ← Back
        </a>

        <h1>
            My Cards
        </h1>

        <span aria-hidden="true"></span>
    </header>

    <div
        class="card-holder-page__filters"
        role="group"
        aria-label="Card type"
    >
        <button
            type="button"
            class="is-active"
            data-full-card-filter="loyalty"
        >
            Rewards
        </button>

        <button
            type="button"
            data-full-card-filter="membership"
        >
            Membership
        </button>
    </div>

    <div class="card-holder-page__stack">

        @foreach ($cards as $card)
            <div
                class="card-holder-page__item"
                data-full-card
                data-card-type="{{ $card['type'] }}"
                style="--card-index: {{ $loop->index }};"
                @if ($card['type'] !== 'loyalty')
                    hidden
                @endif
            >
                <x-cards.card
                    :name="$card['name']"
                    :subtitle="$card['subtitle']"
                    :type="$card['type']"
                    :background="$card['background']"
                    :foreground="$card['foreground']"
                    :href="route('cards.show', $card['slug'])"
                    :info-href="route('cards.info', $card['slug'])"
                />
            </div>
        @endforeach

    </div>
</div>

@endsection