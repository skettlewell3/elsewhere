@extends('layouts.app')

@section('title', 'Join ' . $app->name)

@section('content')
    <section class="app-enrolment">
        <div class="app-enrolment__content">
            <a
                href="{{ route('discover') }}"
                class="app-enrolment__back"
            >
                &larr; Back to Discover
            </a>

            <header class="app-enrolment__header">
                @if($app->category)
                    <p class="app-enrolment__category">
                        {{ $app->category }}
                    </p>
                @endif

                <h1>Join {{ $app->name }}</h1>

                @if($app->description)
                    <p class="app-enrolment__description">
                        {{ $app->description }}
                    </p>
                @endif
            </header>

            <div class="app-enrolment__confirmation">
                @if($membership?->status === 'pending')
                    <h2>Complete your registration</h2>

                    <p>
                        Your registration for {{ $app->name }}
                        has already been started.
                    </p>

                    <p>
                        You can continue the registration
                        process using your existing
                        Elsewhere account.
                    </p>
                @else
                    <h2>Create your app profile</h2>

                    <p>
                        You are about to join {{ $app->name }}
                        using your Elsewhere account.
                    </p>

                    <p>
                        Your Elsewhere account will remain
                        responsible for your login and
                        membership, while {{ $app->name }}
                        will manage your application profile.
                    </p>
                @endif

                @if(session('app_enrolment_error'))
                    <div
                        class="app-enrolment__error"
                        role="alert"
                    >
                        {{ session('app_enrolment_error') }}
                    </div>
                @endif

                <div class="app-enrolment__actions">
                    @if($enrolmentEnabled)
                        <form
                            method="POST"
                            action="{{ route('apps.enrol.store', $app) }}"
                        >
                            @csrf

                            <button
                                type="submit"
                                class="app-enrolment__submit"
                            >
                                {{ $membership?->status === 'pending'
                                    ? 'Continue registration'
                                    : 'Confirm registration' }}
                            </button>
                        </form>
                    @else
                        <button
                            type="button"
                            class="app-enrolment__submit"
                            disabled
                        >
                            Registration coming soon
                        </button>
                    @endif

                    <a
                        href="{{ route('discover') }}"
                        class="app-enrolment__cancel"
                    >
                        Cancel
                    </a>
                </div>
            </div>
        </div>
    </section>
@endsection
