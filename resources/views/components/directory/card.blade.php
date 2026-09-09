@props([
    'business'
])

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

            <div class="directoryCardTags">
                @foreach ($business['categories'] as $category)
                    <span
                        class="directoryCardTag directoryCardCategory"
                        data-category="{{ $category['slug'] }}"
                    >
                        {{ $category['name'] }}
                    </span>
                @endforeach

                @if ($business['offers_ep_redemption'])
                    <span
                        class="directoryCardTag directoryCardReward"
                    >
                        Rewards available
                    </span>
                @endif

            </div>
        </div>

        <div class="directoryCardActions">
            <button
                type="button"
                class="directoryCardFocus"
                aria-label="Find {{ $business['name'] }} on map"
                title="Find on map"
            >
                ⌖
            </button>

            <button
                type="button"
                class="directoryCardExpand"
                aria-label="Expand {{ $business['name'] }}"
                title="Expand"
            >
                +
            </button>
        </div>
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
        </div>
    </div>
</article>