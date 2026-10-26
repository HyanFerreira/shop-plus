<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        @if (request()->routeIs('login', 'register', 'password.request'))
            @include('partials.auth-fonts')
        @else
            <link rel="preconnect" href="https://fonts.bunny.net">
            <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800,900&display=swap" rel="stylesheet" />
        @endif

        <!-- Render-blocking CSS stays in the head in both Vite delivery modes. -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        @stack('auth-image-preload')

        <!-- Styles -->
        @livewireStyles
    </head>
    <body>
        <div class="ds-page">
            {{ $slot }}
        </div>

        @livewireScripts
    </body>
</html>
