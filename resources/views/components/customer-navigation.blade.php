@php
    $current = request()->routeIs('dashboard') ? 'Visão geral' : (request()->routeIs('orders.*') ? 'Meus pedidos' : (request()->routeIs('personal-data.*') ? 'Meus dados' : 'Perfil e segurança'));
@endphp
<aside {{ $attributes->class(['customer-navigation', 'ds-panel']) }} aria-label="Área do cliente">
    <div class="customer-navigation__identity"><span class="customer-navigation__avatar" aria-hidden="true">{{ mb_substr(Auth::user()->name, 0, 1) }}</span><div><p>Minha conta</p><strong>{{ Auth::user()->name }}</strong></div></div>
    <details class="customer-navigation__disclosure" open x-data="{}" x-init="const media = window.matchMedia('(min-width: 1024px)'); $el.open = media.matches; media.addEventListener('change', event => $el.open = event.matches)">
        <summary><span><strong>Minha conta</strong><span>{{ $current }}</span></span><x-icon name="chevron-down" class="size-5" /></summary>
        @include('partials.customer-navigation-links')
    </details>
</aside>
