<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800,900&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <!-- Styles -->
        @livewireStyles
    </head>
    <body>
        <x-banner />

        <div class="ds-page">
            @if (request()->routeIs('api-tokens.*'))
                @livewire('navigation-menu')
            @else
                <x-storefront-header />
            @endif

            <!-- Page Heading -->
            @if (isset($header))
                <header class="border-b border-slate-200 bg-white">
                    <div class="ds-container py-6">
                        {{ $header }}
                    </div>
                </header>
            @endif

            <!-- Page Content -->
            <main id="storefront-content" tabindex="-1">
                {{ $slot }}
            </main>
            @unless (request()->routeIs('api-tokens.*'))
                <x-storefront-footer />
            @endunless
        </div>

        @stack('modals')

        @livewireScripts
    </body>
</html>
