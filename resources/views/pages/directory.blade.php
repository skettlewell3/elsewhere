@extends('layouts.app')

@vite([
    'resources/js/directory.js'
])

@php
    $mapBusinesses = $businesses;
@endphp

<script>
    window.directoryBusinesses = @json($mapBusinesses);
    window.directoryMapScope = @json($mapScope);
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

    <x-directory.panel 
        :businesses="$businesses" 
        :categories="$categories"
    />
</div>

@endsection