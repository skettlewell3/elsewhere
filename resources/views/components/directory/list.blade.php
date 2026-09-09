@props([
    'businesses'
])

<div class="directoryList">

    <div class="directoryDeck">

        @foreach ($businesses as $business)

            <x-directory.card :business="$business" />

        @endforeach

    </div>

</div>