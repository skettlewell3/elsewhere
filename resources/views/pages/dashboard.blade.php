@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')

<div class="dashboard">
    <div class="dashboard-primary">
        <div class="dashboard-side">
            <x-dashboard.verification />
            <x-dashboard.wallet />
            <x-dashboard.card-holder />
        </div>

        <div class="dashboard-main">
            <x-dashboard.calendar />
            <x-dashboard.favourites />
        </div>
    </div>

</div>

@endsection