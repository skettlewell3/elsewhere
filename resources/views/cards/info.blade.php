@extends('layouts.app')

@section('hide-navbar', 'true')

@section('content')

@php
    $cards = [
        'coffee-house' => [
            'name' => 'Coffee House',
            'subtitle' => 'Rewards',
            'type' => 'loyalty',
            'background' => '#8B4513',
            'foreground' => '#ffffff',
        ],

        'southampton-film-club' => [
            'name' => 'Southampton Film Club',
            'subtitle' => 'Member',
            'type' => 'membership',
            'background' => '#173B6C',
            'foreground' => '#ffffff',
        ],

        'the-crown' => [
            'name' => 'The Crown',
            'subtitle' => 'Rewards',
            'type' => 'loyalty',
            'background' => '#17633D',
            'foreground' => '#ffffff',
        ],

        'city-gym' => [
            'name' => 'City Gym',
            'subtitle' => 'Gold Member',
            'type' => 'membership',
            'background' => '#632E79',
            'foreground' => '#ffffff',
        ],

        'local-coffee-co' => [
            'name' => 'Local Coffee Co.',
            'subtitle' => 'Rewards',
            'type' => 'loyalty',
            'background' => '#B85C16',
            'foreground' => '#ffffff',
        ],

        'harbour-club' => [
            'name' => 'Harbour Club',
            'subtitle' => 'Member',
            'type' => 'membership',
            'background' => '#126E82',
            'foreground' => '#ffffff',
        ],

        'the-bakery' => [
            'name' => 'The Bakery',
            'subtitle' => 'Rewards',
            'type' => 'loyalty',
            'background' => '#A9364B',
            'foreground' => '#ffffff',
        ],

        'city-arts' => [
            'name' => 'City Arts',
            'subtitle' => 'Member',
            'type' => 'membership',
            'background' => '#8A6A17',
            'foreground' => '#ffffff',
        ],
    ];

    $card = $cards[$cardSlug] ?? null;
@endphp

@if (!$card)

    <div class="card-info-page">
        <header class="card-info-page__header">
            <a href="{{ route('cards.index') }}">
                ← Back
            </a>

            <h1>
                Card not found
            </h1>

            <span aria-hidden="true"></span>
        </header>
    </div>

@else

    <div class="card-info-page">

        <header class="card-info-page__header">
            <a href="{{ url()->previous() }}">
                ← Back
            </a>

            <h1>
                {{ $card['name'] }}
            </h1>

            <span aria-hidden="true"></span>
        </header>

        <div class="card-info-page__card">
            <x-cards.card
                :name="$card['name']"
                :subtitle="$card['subtitle']"
                :type="$card['type']"
                :background="$card['background']"
                :foreground="$card['foreground']"
                :href="route('cards.show', $cardSlug)"
            />
        </div>

        <section class="card-info-page__section">

            @if ($card['type'] === 'loyalty')

                <h2>
                    Rewards programme
                </h2>

                <p>
                    Information about this loyalty programme will appear here.
                </p>

                <h3>
                    How it works
                </h3>

                <p>
                    Earning requirements, qualifying purchases and reward conditions will appear here.
                </p>

                <h3>
                    Rewards
                </h3>

                <p>
                    Available loyalty rewards and redemption conditions will appear here.
                </p>

            @else

                <h2>
                    Membership
                </h2>

                <p>
                    Information about this membership will appear here.
                </p>

                <h3>
                    Eligibility
                </h3>

                <p>
                    Membership requirements and application information will appear here.
                </p>

                <h3>
                    Benefits
                </h3>

                <p>
                    Membership benefits, tiers and conditions will appear here.
                </p>

            @endif

        </section>

    </div>

@endif

@endsection