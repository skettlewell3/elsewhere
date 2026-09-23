@extends('layouts.app')

@section('content')

<div class="businessPage">

    @include('businesses.components.header', [
        'business' => $business,
        'businessLocation' => $businessLocation,
        'pageMode' => $pageMode,
    ])

    @include('businesses.components.about', [
        'business' => $business,
    ])

    @if($businessLocation)
        @include('businesses.components.location-details', [
            'businessLocation' => $businessLocation,
            'pageMode' => $pageMode,
        ])
    @endif

    @if($businessLocations->count() > 1)
        @include('businesses.components.locations', [
            'business' => $business,
            'businessLocations' => $businessLocations,
            'businessLocation' => $businessLocation,
        ])
    @endif

</div>

@endsection