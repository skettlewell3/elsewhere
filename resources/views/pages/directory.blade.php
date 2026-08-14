@extends('layouts.app')

@vite('resources/js/directory.js')

@php
    $mapBusinesses = $businesses->map(function ($business) {
        return [
            'id' => $business['id'],
            'name' => $business['name'],
            'description' => $business['description'],
            'website_url' => $business['website_url'],
            'latitude' => $business['latitude'],
            'longitude' => $business['longitude'],
            'coordinate_source' => $business['coordinate_source'],
            'location' => $business['location']
                ? [
                    'id' => $business['location']->id,
                    'name' => $business['location']->name,
                    'type' => $business['location']->type,
                ]
                : null,
        ];
    });
@endphp

<script>
    window.directoryBusinesses = @json($mapBusinesses);
</script>

@section('content')

<div class="directory">

    <div class="directoryMap">

        <div id="directory-map"></div>

    </div>


    <aside class="directoryPanel">

        <div class="directoryPanelHeader">

            <div class="directorySearch">

                <input
                    type="search"
                    placeholder="Search businesses..."
                    aria-label="Search businesses"
                >

            </div>

            <button
                type="button"
                class="directoryPanelToggle"
                aria-label="Collapse directory"
            >
                ×
            </button>

        </div>


        <div class="directoryFilters">

            <div class="directoryCategories">

                <button
                    type="button"
                    class="directoryCategory active"
                >
                    All
                </button>

                <button
                    type="button"
                    class="directoryCategory"
                >
                    Food & Drink
                </button>

                <button
                    type="button"
                    class="directoryCategory"
                >
                    Shopping
                </button>

                <button
                    type="button"
                    class="directoryCategory"
                >
                    Services
                </button>

            </div>

        </div>


        <div class="directorySummary">

            <strong>Directory</strong>

            <span>
                {{ $businesses->count() }} businesses
            </span>

        </div>


        <div class="directoryList">

            <div class="directoryDeck">

                @foreach ($businesses as $business)

                    <article
                        class="directoryCard"
                        data-business-id="{{ $business['id'] }}"
                    >

                        <div class="directoryCardHeader">

                            <div class="directoryCardTitle">
                                <div class="directoryCardName">
                                    <h2>
                                        {{ $business['name'] }}
                                    </h2>

                                    <span class="directoryCardDivider"></span>
                                </div>

                                <span class="directoryCardCategory">
                                    Business
                                </span>
                            </div>

                            <button
                                type="button"
                                class="directoryCardExpand"
                                aria-label="Expand {{ $business['name'] }}"
                            >
                                +
                            </button>

                        </div>


                        <div class="directoryCardBody">

                            <p class="directoryCardDescription">
                                {{ $business['description'] }}
                            </p>

                            @if ($business['location'])

                                <div class="directoryCardLocation">

                                    {{ $business['location']->name }}

                                </div>

                            @endif


                            <div class="directoryCardDetails">

                                <p>
                                    {{ $business['description'] }}
                                </p>

                                @if ($business['website_url'])

                                    <a
                                        href="{{ $business['website_url'] }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                    >
                                        Visit website
                                    </a>

                                @endif

                                <button
                                    type="button"
                                    class="directoryCardFocus"
                                >
                                    Focus on map
                                </button>

                            </div>

                        </div>

                    </article>

                @endforeach

            </div>

        </div>

    </aside>

</div>

@endsection