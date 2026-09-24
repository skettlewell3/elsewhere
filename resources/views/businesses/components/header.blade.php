<header class="businessHeader">

    <h1 class="businessHeaderName">
        {{ $business->name }}
    </h1>

    @if($pageMode === 'branch' && $businessLocation)
        <p class="businessHeaderContext">
            {{ $businessLocation->name }}
        </p>
    @elseif($pageMode === 'hybrid')
        <p class="businessHeaderContext">
            Head Office
        </p>
    @endif

    @if($businessPage?->headline)
        <p class="businessHeaderHeadline">
            {{ $businessPage->headline }}
        </p>
    @endif

    <div class="businessHeaderActions">

        @if($business->website_url)
            <a
                class="businessWebsiteLink"
                href="{{ $business->website_url }}"
                target="_blank"
                rel="noopener noreferrer"
            >
                Visit website
            </a>
        @endif

        @if(
            $pageMode === 'branch' &&
            $businessLocations->count() > 1
        )
            <a
                class="businessOrganisationLink"
                href="{{ route(
                    'directory.businesses.overview',
                    [
                        'business' => $business->slug,
                    ]
                ) }}"
            >
                View business
            </a>
        @endif

    </div>

</header>