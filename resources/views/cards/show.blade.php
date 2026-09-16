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

    <div class="card-page">
        <header class="card-page__header">
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

    <div class="card-page">

        <header class="card-page__header">
            <a href="{{ route('cards.index') }}">
                ← Back
            </a>

            <h1>
                {{ $card['name'] }}
            </h1>

            <span aria-hidden="true"></span>
        </header>

        <div class="card-page__card">
            <x-cards.card
                :name="$card['name']"
                :subtitle="$card['subtitle']"
                :type="$card['type']"
                :background="$card['background']"
                :foreground="$card['foreground']"
                :info-href="route('cards.info', $cardSlug)"
                :show-qr="true"
            />
        </div>

        <div
            class="card-credential-overlay"
            data-card-credential-overlay
            hidden
        >
            <button
                type="button"
                class="card-credential-overlay__close"
                data-card-credential-close
                aria-label="Close card"
            >
                ×
            </button>

            <div
                class="card-credential"
                style="
                    --card-background: {{ $card['background'] }};
                    --card-foreground: {{ $card['foreground'] }};
                "
            >
                <div class="card-credential__business">
                    {{ $card['name'] }}
                </div>
            
                <div class="card-credential__content">
            
                    <div class="card-credential__details">
                        <span class="card-credential__name">
                            Sam K
                        </span>
            
                        <span class="card-credential__tier">
                            {{ $card['subtitle'] }}
                        </span>
            
                        <span class="card-credential__number">
                            #004827
                        </span>
                    </div>
            
                    <div class="card-credential__qr">
                        <div class="card-credential__qr-placeholder">
                            QR
                        </div>
                    </div>
            
                </div>
            </div>
        </div>

        <div class="card-page__content">

            @if ($card['type'] === 'loyalty')

                <section class="card-page__section">
                    <h2>
                        Rewards
                    </h2>

                    <p>
                        Loyalty rewards for this card will appear here.
                    </p>
                </section>

                <section class="card-page__section">
                    <h2>
                        Recent activity
                    </h2>

                    <p>
                        Qualifying loyalty activity will appear here.
                    </p>
                </section>

            @else

                <section class="card-page__section">
                    <h2>
                        Membership
                    </h2>

                    <p>
                        Membership details will appear here.
                    </p>
                </section>

                <section class="card-page__section">
                    <h2>
                        Recent activity
                    </h2>

                    <p>
                        Membership activity will appear here.
                    </p>
                </section>

            @endif

        </div>

    </div>

@endif

@endsection