<header class="storefront-header">
    <a href="#storefront-content" class="storefront-skip-link">Pular para o conteúdo</a>
    <div class="ds-storefront-container storefront-header__inner">
        <x-shop-logo class="storefront-header__logo" />

        <form action="{{ route('catalog.index') }}" method="get" role="search" class="storefront-search">
            <label class="sr-only" for="storefront-search">Buscar produtos, marcas ou categorias</label>
            <x-input id="storefront-search" type="search" name="q" :value="request('q', '')" class="storefront-search__input" placeholder="Buscar produtos, marcas ou categorias..." />
            <button type="submit" class="storefront-search__submit" aria-label="Buscar produtos">
                <x-icon name="magnifying-glass" class="size-5" />
            </button>
        </form>

        <nav class="storefront-nav" aria-label="Navegação principal">
            <a href="{{ route('home') }}" @if(request()->routeIs('home')) aria-current="page" @endif>Início</a>
            <a href="{{ route('catalog.index') }}" @if(request()->routeIs('catalog.index')) aria-current="page" @endif>Categorias</a>
            <a href="{{ route('home') }}#ofertas">Ofertas</a>
            <a href="{{ route('catalog.index') }}">Vender</a>
        </nav>

        <div class="storefront-header__actions">
            @auth
                <livewire:header-cart />
                @php
                    $firstName = preg_split('/[\s\p{Z}]+/u', Auth::user()->name, -1, PREG_SPLIT_NO_EMPTY)[0] ?? 'Conta';
                    preg_match('/^\X/u', $firstName, $initial);
                @endphp
                <div class="storefront-user" x-data="{ open: false }"
                    @click.outside="open = false"
                    @keydown.escape.stop.prevent="if (open) { open = false; $refs.trigger.focus() }"
                    @focusout="if (!$el.contains($event.relatedTarget)) open = false">
                    <button type="button" class="ds-button ds-button--secondary storefront-user__trigger"
                        x-ref="trigger" aria-controls="storefront-account-menu" aria-expanded="false" :aria-expanded="open.toString()"
                        @click="open = !open"
                        @keydown.arrow-down.prevent="open = true; $nextTick(() => $refs.menu.querySelector('a').focus())">
                        <span class="storefront-user__avatar" aria-hidden="true">{{ mb_strtoupper($initial[0] ?? '') }}</span>
                        <span class="storefront-user__name">{{ $firstName }}</span>
                        <x-icon name="chevron-down" class="size-5" />
                    </button>
                    <div id="storefront-account-menu" class="storefront-user__menu" x-ref="menu" x-show="open" style="display: none;">
                        @include('partials.customer-navigation-links')
                    </div>
                </div>
            @else
                <a href="{{ route('login') }}" class="ds-button ds-button--primary storefront-login">Entrar</a>
            @endauth
        </div>
    </div>
</header>
