@extends('layouts.app')

@section('title', 'Discover')

@section('content')
    <div class="apps-grid">
        @foreach(config('apps') as $app)
            <x-apps.appcard :app="$app" />
        @endforeach
    </div>
@endsection