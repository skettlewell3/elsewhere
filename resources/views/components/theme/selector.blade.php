@php
    $themes = config('themes');
@endphp

<div class="theme-selector">

    <x-theme.mode-toggle />

    @foreach($themes as $key => $theme)

        <form method="POST" action="/theme">

            @csrf

            <x-theme.card
                :theme="$theme"
                :key="$key"
            />

        </form>

    @endforeach

</div>