@extends('layouts.app')

@vite([
    'resources/css/directory.css',
    'resources/js/directory.js'
])

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

            'categories' => $business['categories'] ?? [],
        ];
    });
@endphp

<script>
    window.directoryBusinesses = @json($mapBusinesses);
</script>

@section('content')

<div 
class="directory"
data-directory
data-panel-state="expanded"
>
    <div class="directoryMap">
        <div id="directory-map"></div>
    </div>

    <x-directory.panel :businesses="$businesses" />
</div>

@endsection