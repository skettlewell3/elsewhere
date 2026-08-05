@extends('layouts.app')

@section('title', 'Apps')

@section('content')
    <!-- <h1>Apps</h1> -->

    <div class="apps-grid">
        @foreach(config('apps') as $app)
            <x-apps.appcard :app="$app" />
        @endforeach
    </div>
@endsection