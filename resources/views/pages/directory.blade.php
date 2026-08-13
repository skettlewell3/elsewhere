@extends('layouts.app')

@vite('resources/js/directory.js')

@php
    $mapBusinesses = $businesses->map(function ($business) {
        return [
            'id' => $business['id'],
            'name' => $business['name'],
            'description' => $business['description'],
            'website_url' => $business['website_url'],
            'latitude' => $business['latitude'],
            'longitude' => $business['longitude'],
            'coordinate_source' => $business['coordinate_source'],
            'location' => $business['location']
                ? [
                    'id' => $business['location']->id,
                    'name' => $business['location']->name,
                    'type' => $business['location']->type,
                ]
                : null,
        ];
    });
@endphp

<script>
    window.directoryBusinesses = @json($mapBusinesses);
</script>

@section('content')

<div class="max-w-6xl mx-auto px-4 py-10">

    <h1 class="text-4xl font-bold">
        Directory
    </h1>

    <div class="mt-8 space-y-4">

        <div
            id="directory-map"
            class="w-full h-[500px] rounded-lg"
        ></div>

        @foreach ($businesses as $business)

            <div class="p-4 border rounded-lg">

                <h2 class="text-xl font-semibold">
                    {{ $business['name'] }}
                </h2>

                <p class="mt-2 text-gray-600">
                    {{ $business['description'] }}
                </p>

                <p class="mt-2 text-sm text-gray-500">
                    {{ $business['latitude'] }},
                    {{ $business['longitude'] }}
                </p>

            </div>

        @endforeach

    </div>

</div>

@endsection