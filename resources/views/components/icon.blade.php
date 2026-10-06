@props(['name', 'strokeWidth' => 1.8])

<svg {{ $attributes->class(['shrink-0'])->merge(['fill' => 'none', 'viewBox' => '0 0 24 24', 'stroke' => 'currentColor', 'aria-hidden' => 'true', 'stroke-width' => $strokeWidth]) }}>
    @switch($name)
        @case('shopping-bag')<path stroke-linecap="round" stroke-linejoin="round" d="M16.5 9.4V7.5a4.5 4.5 0 0 0-9 0v1.9m-3.75 0h16.5l-1.125 10.125a2.25 2.25 0 0 1-2.237 2.003H7.112a2.25 2.25 0 0 1-2.237-2.003L3.75 9.4Z" />@break
        @case('shopping-cart')<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.43M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h12.75l-1.5-9H5.106m2.394 9L5.106 5.265M7.5 14.25l-2.394-8.985M6.75 21a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />@break
        @case('magnifying-glass')<path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35m1.35-5.4a6.75 6.75 0 1 1-13.5 0 6.75 6.75 0 0 1 13.5 0Z" />@break
        @case('device-phone-mobile')<path stroke-linecap="round" stroke-linejoin="round" d="M10.5 20.25h3m-9-16.5h15m-15 0a2.25 2.25 0 0 0-2.25 2.25v12A2.25 2.25 0 0 0 4.5 20.25m15-16.5a2.25 2.25 0 0 1 2.25 2.25v12a2.25 2.25 0 0 1-2.25 2.25m-15-16.5h15" />@break
        @case('computer-desktop')<path stroke-linecap="round" stroke-linejoin="round" d="M9.75 17.25v1.5m0 0h4.5m-4.5 0H6.375a2.625 2.625 0 0 1-2.625-2.625V5.625A2.625 2.625 0 0 1 6.375 3h11.25a2.625 2.625 0 0 1 2.625 2.625V16.125a2.625 2.625 0 0 1-2.625 2.625H14.25" />@break
        @case('home')<path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955a1.125 1.125 0 0 1 1.592 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125h4.5v-6.75h3.75V21h4.5c.621 0 1.125-.504 1.125-1.125V9.75" />@break
        @case('shirt')<path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375 16.5 3.75l-2.25 2.625h-4.5L7.5 3.75 3.75 6.375l2.625 3v10.875h11.25V9.375l2.625-3Z" />@break
        @case('trophy')<path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 0 1-3-3v-1.5m3 4.5h1.125a2.625 2.625 0 0 0 2.625-2.625V6.75h-3.75m-9 12H6.375A2.625 2.625 0 0 1 3.75 16.125V6.75H7.5m0 0V3.375h9V6.75m-9 0h9m-4.5 7.5v-4.5" />@break
        @case('sparkles')<path stroke-linecap="round" stroke-linejoin="round" d="m9.813 15.904 3.75-8.437 3.75 8.437-3.75 8.437-3.75-8.437ZM4.5 9.75l1.125-2.25L7.875 6.375 5.625 5.25 4.5 3 3.375 5.25 1.125 6.375 3.375 7.5 4.5 9.75Zm15 0 1.125-2.25 2.25-1.125-2.25-1.125L19.5 3l-1.125 2.25-2.25 1.125 2.25 1.125L19.5 9.75Z" />@break
        @case('truck')<path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Zm10.5 0a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0ZM3 4.5h11.25v12H3v-12Zm11.25 4.5h3l3 3v4.5h-6V9Z" />@break
        @case('book-open')<path stroke-linecap="round" stroke-linejoin="round" d="M12 6.75a3 3 0 0 0-3-3H4.5A1.5 1.5 0 0 0 3 5.25v13.5a1.5 1.5 0 0 0 1.5 1.5H9a3 3 0 0 1 3 3m0-16.5a3 3 0 0 1 3-3h4.5A1.5 1.5 0 0 1 21 5.25v13.5a1.5 1.5 0 0 1-1.5 1.5H15a3 3 0 0 0-3 3m0-16.5v16.5" />@break
        @case('heart')<path stroke-linecap="round" stroke-linejoin="round" d="M21.435 8.98c0 4.736-9.435 9.27-9.435 9.27S2.565 13.716 2.565 8.98c0-3.043 2.468-5.51 5.511-5.51 1.793 0 3.386.86 4.424 2.19 1.038-1.33 2.63-2.19 4.424-2.19 3.043 0 5.511 2.467 5.511 5.51Z" />@break
        @case('credit-card')<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5m-19.5 0A2.25 2.25 0 0 1 4.5 6h15a2.25 2.25 0 0 1 2.25 2.25m-19.5 0v7.5A2.25 2.25 0 0 0 4.5 18h15a2.25 2.25 0 0 0 2.25-2.25v-7.5m-15 5.25h3" />@break
        @case('lock-closed')<path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 0 0-9 0v3.75m-3 0h15A1.5 1.5 0 0 1 21 12v9a1.5 1.5 0 0 1-1.5 1.5h-15A1.5 1.5 0 0 1 3 21v-9a1.5 1.5 0 0 1 1.5-1.5Z" />@break
        @case('envelope')<path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0l-7.5-4.615A2.25 2.25 0 0 1 2.25 6.993V6.75" />@break
        @case('check')<path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />@break
        @case('arrow-left')<path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />@break
        @case('bell')<path stroke-linecap="round" stroke-linejoin="round" d="M14.25 18.75a2.25 2.25 0 0 1-4.5 0m9-4.5V10.5a6.75 6.75 0 0 0-13.5 0v3.75L3.75 16.5h16.5l-1.5-2.25Z" />@break
        @case('ellipsis-horizontal')<path stroke-linecap="round" stroke-linejoin="round" d="M6.75 12a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm6 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm6 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />@break
        @default <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Z" />
    @endswitch
</svg>
