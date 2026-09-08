@php
    $themes = config('themes');
@endphp

<div class="theme-selector">

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