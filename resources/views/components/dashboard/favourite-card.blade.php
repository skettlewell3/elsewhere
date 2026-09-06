@props([
    'name',
    'image',
    'alt' => '',
    'type' => 'app',
    'category' => null,
    'url' => '#',
])

<a
    href="{{ $url }}"
    class="
        favourite-card
        favourite-card-{{ $type }}
    "
>

    <div class="favourite-card-media">
        <img
            src="{{ asset($image) }}"
            alt="{{ $alt }}"
        >
    </div>

    <div class="favourite-card-footer">

        <div class="favourite-card-info">

            <span class="favourite-card-name">
                {{ $name }}
            </span>

            <span class="favourite-card-meta">
                {{ $category ?? ucfirst($type) }}
            </span>

        </div>

        <span class="favourite-card-favourite">
            ★
        </span>

    </div>

</a>