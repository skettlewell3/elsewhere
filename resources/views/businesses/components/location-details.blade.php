@php
    $isServiceArea =
        $businessLocation->role ===
        \App\Enums\BusinessLocationRole::ServiceArea;

    $hasAddress =
        $businessLocation->address_line_1 ||
        $businessLocation->address_line_2 ||
        $businessLocation->postcode;

    $hasContact =
        $businessLocation->phone ||
        $businessLocation->email;

    $preciseLocation =
        $businessLocation->location?->name;

    $canonicalLocation =
        $businessLocation->canonicalLocation?->name;

    /*
     * Physical locations prioritise their address.
     *
     * Service-area businesses prioritise contact details because the
     * geographical/service-area view will eventually be represented
     * more meaningfully by the map.
     */
    if ($isServiceArea && $hasContact) {
        $primaryView = 'contact';
        $secondaryView = 'location';
    } elseif ($hasAddress) {
        $primaryView = 'address';
        $secondaryView = $hasContact
            ? 'contact'
            : null;
    } elseif ($hasContact) {
        $primaryView = 'contact';
        $secondaryView = 'location';
    } else {
        $primaryView = 'location';
        $secondaryView = null;
    }
@endphp

<section
    class="businessLocationDetails"
    data-business-location-panel
    data-active-view="{{ $primaryView }}"
>

    <div class="businessLocationDetailsHeader">

        <div>

            @if($pageMode === 'hybrid')
                <h2>
                    Head Office
                </h2>
            @elseif($isServiceArea)
                <h2>
                    Service Area
                </h2>
            @else
                <h2>
                    Location
                </h2>
            @endif

            <div class="businessLocationName">
                {{ $businessLocation->name }}
            </div>

        </div>


        @if($secondaryView)
            <button
                type="button"
                class="businessLocationToggle"
                data-business-location-toggle
                data-primary-view="{{ $primaryView }}"
                data-secondary-view="{{ $secondaryView }}"
            >
                @if($primaryView === 'contact')
                    View location
                @else
                    View contact details
                @endif
            </button>
        @endif

    </div>


    <div class="businessLocationViews">

        {{-- ADDRESS VIEW --}}

        @if($hasAddress)
            <div
                class="businessLocationView"
                data-business-location-view="address"
                @if($primaryView !== 'address') hidden @endif
            >

                <address class="businessLocationAddress">

                    @if($businessLocation->address_line_1)
                        <div>
                            {{ $businessLocation->address_line_1 }}
                        </div>
                    @endif

                    @if($businessLocation->address_line_2)
                        <div>
                            {{ $businessLocation->address_line_2 }}
                        </div>
                    @endif

                    @if($businessLocation->postcode)
                        <div>
                            {{ $businessLocation->postcode }}
                        </div>
                    @endif

                </address>

                @if($business->website_url)
                    <a
                        class="businessLocationWebsite"
                        href="{{ $business->website_url }}"
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        Visit website
                    </a>
                @endif

            </div>
        @endif


        {{-- CONTACT VIEW --}}

        @if($hasContact)
            <div
                class="businessLocationView"
                data-business-location-view="contact"
                @if($primaryView !== 'contact') hidden @endif
            >

                <div class="businessLocationContact">

                    @if($businessLocation->phone)
                        <div class="businessLocationPhone">
                            <span>
                                Phone
                            </span>

                            <a href="tel:{{ $businessLocation->phone }}">
                                {{ $businessLocation->phone }}
                            </a>
                        </div>
                    @endif

                    @if($businessLocation->email)
                        <div class="businessLocationEmail">
                            <span>
                                Email
                            </span>

                            <a href="mailto:{{ $businessLocation->email }}">
                                {{ $businessLocation->email }}
                            </a>
                        </div>
                    @endif

                </div>

                @if($business->website_url)
                    <a
                        class="businessLocationWebsite"
                        href="{{ $business->website_url }}"
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        Visit website
                    </a>
                @endif

            </div>
        @endif


        {{-- LOCATION / SERVICE-AREA FALLBACK VIEW --}}

        <div
            class="businessLocationView"
            data-business-location-view="location"
            @if($primaryView !== 'location') hidden @endif
        >

            <div class="businessLocationFallback">

                @if($isServiceArea)
                    <div class="businessLocationFallbackLabel">
                        Service location
                    </div>
                @else
                    <div class="businessLocationFallbackLabel">
                        Location
                    </div>
                @endif

                @if($preciseLocation)
                    <div class="businessLocationArea">
                        {{ $preciseLocation }}
                    </div>
                @endif

                @if(
                    $canonicalLocation &&
                    $canonicalLocation !== $preciseLocation
                )
                    <div class="businessLocationCanonical">
                        {{ $canonicalLocation }}
                    </div>
                @endif

            </div>

            @if($business->website_url)
                <a
                    class="businessLocationWebsite"
                    href="{{ $business->website_url }}"
                    target="_blank"
                    rel="noopener noreferrer"
                >
                    Visit website
                </a>
            @endif

        </div>

    </div>

</section>