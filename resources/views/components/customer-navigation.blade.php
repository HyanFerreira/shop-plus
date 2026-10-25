@php
    $current = request()->routeIs('dashboard') ? 'Visão geral' : (request()->routeIs('orders.*') ? 'Meus pedidos' : (request()->routeIs('personal-data.*') ? 'Meus dados' : 'Perfil e segurança'));
@endphp
<aside {{ $attributes->class(['customer-navigation', 'ds-panel']) }} aria-label="Área do cliente">
    <div class="customer-navigation__identity"><span class="customer-navigation__avatar" aria-hidden="true">{{ mb_substr(Auth::user()->name, 0, 1) }}</span><div><p>Minha conta</p><strong>{{ Auth::user()->name }}</strong></div></div>
    <details class="customer-navigation__disclosure" open x-data="{}" x-init="const media = window.matchMedia('(min-width: 1024px)'); $el.open = media.matches; media.addEventListener('change', event => $el.open = event.matches)">
        <summary><span><strong>Minha conta</strong><span>{{ $current }}</span></span><x-icon name="chevron-down" class="size-5" /></summary>
        <nav aria-label="Navegação da conta">
            <a href="{{ route('dashboard') }}" @if(request()->routeIs('dashboard')) aria-current="page" @endif><x-icon name="home" class="size-5" />Visão geral</a>
            <a href="{{ route('orders.index') }}" @if(request()->routeIs('orders.*')) aria-current="page" @endif><x-icon name="shopping-bag" class="size-5" />Meus pedidos</a>
            <a href="{{ route('personal-data.show') }}" @if(request()->routeIs('personal-data.*')) aria-current="page" @endif><x-icon name="user" class="size-5" />Meus dados</a>
            <a href="{{ route('profile.show') }}" @if(request()->routeIs('profile.*')) aria-current="page" @endif><x-icon name="lock-closed" class="size-5" />Perfil e segurança</a>
            @if(Auth::user()->isAdmin())<a href="{{ route('admin.dashboard') }}"><x-icon name="computer-desktop" class="size-5" />Administração</a>@endif
        </nav>
        <form method="POST" action="{{ route('logout') }}" class="customer-navigation__logout">@csrf<button type="submit"><x-icon name="arrow-left" class="size-5" />Sair da conta</button></form>
    </details>
</aside>