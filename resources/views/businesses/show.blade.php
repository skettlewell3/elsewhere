@extends('layouts.app')

@section('content')

<div>
    <h1>{{ $business->name }}</h1>

    <p>{{ $business->description }}</p>

    <p>
        {{ $business->canonicalLocation->name }}
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