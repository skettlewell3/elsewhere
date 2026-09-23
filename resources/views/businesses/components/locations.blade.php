<section class="businessLocations">

    <h2>
        Locations
    </h2>

    <div class="businessLocationsList">

        @foreach($businessLocations as $location)

            @php
                $isCurrentLocation =
                    $businessLocation &&
                    $businessLocation->id === $location->id;

                $isHeadOffice =
                    $location->role ===
                    \App\Enums\BusinessLocationRole::HeadOffice;

                $locationUrl = $isHeadOffice
                    ? route(
                        'directory.businesses.overview',
                        [
                            'business' => $business->slug,
                        ]
                    )
                    : route(
                        'directory.businesses.show',
                        [
                            'location' =>
                                $location->canonicalLocation->slug,

                            'business' =>
                                $location->slug,
                        ]
                    );
            @endphp

            <article
                class="businessLocationCard{{ $isCurrentLocation ? ' active' : '' }}"
            >

                <h3 class="businessLocationCardName">
                    {{ $location->name }}
                </h3>

                @if($isHeadOffice)
                    <div class="businessLocationCardRole">
                        Head Office
                    </div>
                @elseif(
                    $location->role ===
                    \App\Enums\BusinessLocationRole::ServiceArea
                )
                    <div class="businessLocationCardRole">
                        Service Area
                    </div>
                @endif

                @if($location->location)
                    <div class="businessLocationCardArea">
                        {{ $location->location->name }}
                    </div>
                @endif

                @if($isCurrentLocation)
                    <span class="businessLocationCardCurrent">
                        Current location
                    </span>
                @else
                    <a
                        class="businessLocationCardLink"
                        href="{{ $locationUrl }}"
                    >
                        View location
                    </a>
                @endif

            </article>

        @endforeach

    </div>

</section>