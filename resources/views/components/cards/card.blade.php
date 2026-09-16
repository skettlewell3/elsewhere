@props([
    'name',
    'subtitle' => null,
    'href' => null,
    'infoHref' => null,
    'background' => '#444444',
    'foreground' => '#ffffff',
    'logo' => null,
    'type' => 'membership',
    'showQr' => false,
])

@php
    $passedStyle = $attributes->get('style', '');

    $cardStyle = implode(' ', [
        '--card-background: ' . $background . ';',
        '--card-foreground: ' . $foreground . ';',
        $passedStyle,
    ]);
@endphp

<article
    {{ $attributes
        ->except('style')
        ->merge([
            'class' => 'platform-card',
        ])
    }}
    style="{{ $cardStyle }}"
    data-card
    data-card-name="{{ strtolower($name) }}"
    data-card-type="{{ $type }}"
>
    @if ($href)
        <a
            href="{{ $href }}"
            class="platform-card__main-link"
            aria-label="Open {{ $name }} card"
        ></a>
    @endif

    <div class="platform-card__header">
        <div class="platform-card__identity">

            @if ($infoHref)
                <a
                    href="{{ $infoHref }}"
                    class="platform-card__info"
                    aria-label="Information about {{ $name }}"
                    title="Card information"
                >
                    i
                </a>
            @endif

            @if ($logo)
                <img
                    src="{{ $logo }}"
                    alt=""
                    class="platform-card__logo"
                >
            @endif

            <span class="platform-card__name">
                {{ $name }}
            </span>

        </div>

        <span
            class="platform-card__type"
            aria-hidden="true"
        >
            {{ $type === 'loyalty' ? 'Rewards' : 'Member' }}
        </span>
    </div>

    <div class="platform-card__body"></div>

    <div class="platform-card__footer">

        @if ($subtitle)
            <span class="platform-card__subtitle">
                {{ $subtitle }}
            </span>
        @endif

        @if ($showQr)
            <button
                type="button"
                class="platform-card__qr"
                data-card-qr
            >
                Show QR
            </button>
        @endif

    </div>
</article>