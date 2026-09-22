@extends('layouts.app')

@section('content')

<div>
    <h1>{{ $business->name }}</h1>

    @if($business->description)
        <p>{{ $business->description }}</p>
    @endif

    <p>
        {{ $businessLocation->canonicalLocation->name }}
    </p>

    @if($business->website_url)
        <a
            href="{{ $business->website_url }}"
            target="_blank"
            rel="noopener noreferrer"
        >
            Visit website
        </a>
    @endif
</div>

@endsection