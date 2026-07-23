<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title>
            @yield('title', 'Elsewhere')
        </title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>

    @php
        $theme = config('themes')[session('theme', 'default')];
    @endphp

    <body class="theme-{{ $theme['category'] }} mode-{{ session('mode', 'dark') }}">
        <x-theme.bglayer />

        <div class="app-shell">
            <x-nav.navbar />

            <main>
                @yield('content')
            </main>

        </div>

        <x-theme.selector />
    </body>
</html>