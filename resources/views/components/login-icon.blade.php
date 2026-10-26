@props(['name'])
<svg {{ $attributes->merge(['viewBox' => '0 0 24 24', 'fill' => 'none', 'stroke' => 'currentColor', 'stroke-width' => '1.8', 'aria-hidden' => 'true']) }} stroke-linecap="round" stroke-linejoin="round">
    @switch($name)
        @case('circle-alert')
            <circle cx="12" cy="12" r="9" /><path d="M12 7v6m0 4h.01" />
            @break
        @case('eye')
            <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z" /><circle cx="12" cy="12" r="3" />
            @break
        @case('eye-slash')
            <path d="m3 3 18 18M10.5 5.1A12 12 0 0 1 12 5c6.5 0 10 7 10 7a18 18 0 0 1-3.2 4.1M6.3 6.3A19 19 0 0 0 2 12s3.5 7 10 7a12 12 0 0 0 5.7-1.7M9.9 9.9a3 3 0 0 0 4.2 4.2" />
            @break
        @case('enter')
            <path d="M14 4h4a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-4M3 12h12m-5-5 5 5-5 5" />
            @break
        @case('user-plus')
            <circle cx="9" cy="7" r="4" /><path d="M2 21v-2a7 7 0 0 1 14 0v2m3-13v8m-4-4h8" />
            @break
    @endswitch
</svg>
