@extends('layouts.app')

@section('title', 'Discover')

@section('content')
    @if(session('app_launch_message'))
        <div class="app-launch-message" role="status">
            {{ session('app_launch_message') }}
        </div>
    @endif

    <div class="apps-grid">
        @forelse($apps as $app)
            <x-apps.appcard
                :app="$app"
                :is-logged-in="$isLoggedIn"
                :membership="$memberships->get($app->app_id)"
            />
        @empty
            <p>No applications are currently available.</p>
        @endforelse
    </div>
@endsection