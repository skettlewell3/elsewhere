
@extends('layouts.app')

@section('title', 'Discover')

@section('content')
    <div class="apps-grid">
        @forelse($apps as $app)
            <x-apps.appcard :app="$app" />
        @empty
            <p>No applications are currently available.</p>
        @endforelse
    </div>
@endsection
