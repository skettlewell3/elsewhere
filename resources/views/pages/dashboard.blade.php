@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')

<div class="dashboard">
    <div class="dashboard-primary">
        <div class="dashboard-side">
            <x-dashboard.wallet />
            <x-dashboard.verification />
        </div>

        <x-dashboard.calendar />
    </div>

    <x-dashboard.favourites />
</div>

@endsection