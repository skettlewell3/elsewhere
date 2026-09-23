<section class="businessLocationDetails">

    @if($pageMode === 'hybrid')
        <h2>
            Head Office
        </h2>
    @elseif($businessLocation->role === \App\Enums\BusinessLocationRole::ServiceArea)
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

    @if(
        $businessLocation->location &&
        $businessLocation->location->name !== $businessLocation->name
    )
        <div class="businessLocationArea">
            {{ $businessLocation->location->name }}
        </div>
    @endif

    @if($businessLocation->canonicalLocation)
        <div class="businessLocationCanonical">
            {{ $businessLocation->canonicalLocation->name }}
        </div>
    @endif

    @if($businessLocation->address_line_1)
        <address class="businessLocationAddress">

            <div>
                {{ $businessLocation->address_line_1 }}
            </div>

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
    @endif

    @if($businessLocation->phone)
        <div class="businessLocationPhone">
            <a href="tel:{{ $businessLocation->phone }}">
                {{ $businessLocation->phone }}
            </a>
        </div>
    @endif

    @if($businessLocation->email)
        <div class="businessLocationEmail">
            <a href="mailto:{{ $businessLocation->email }}">
                {{ $businessLocation->email }}
            </a>
        </div>
    @endif

</section>