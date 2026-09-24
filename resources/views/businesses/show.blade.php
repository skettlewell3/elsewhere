@extends('layouts.app')

@vite([
    'resources/js/business.js'
])

@section('content')

<div class="businessPage">

    <div class="businessTop">

        <div class="businessTopDetails">

            @include('businesses.components.header', [
                'business' => $business,
                'businessPage' => $businessPage,
                'businessLocation' => $businessLocation,
                'businessLocations' => $businessLocations,
                'pageMode' => $pageMode,
            ])

            @include('businesses.components.about', [
                'business' => $business,
                'businessPage' => $businessPage,
            ])

            @if($businessLocation)
                @include('businesses.components.location-details', [
                    'business' => $business,
                    'businessLocation' => $businessLocation,
                    'pageMode' => $pageMode,
                ])
            @endif

        </div>

        @include('businesses.components.map', [
            'business' => $business,
            'businessLocation' => $businessLocation,
            'businessLocations' => $businessLocations,
            'pageMode' => $pageMode,
        ])

    </div>

    @if($businessLocations->count() > 1)
        @include('businesses.components.locations', [
            'business' => $business,
            'businessLocations' => $businessLocations,
            'businessLocation' => $businessLocation,
        ])
    @endif

</div>

@endsection