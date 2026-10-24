<header class="storefront-header">
    <a href="#storefront-content" class="storefront-skip-link">Pular para o conteúdo</a>
    <div class="ds-storefront-container storefront-header__inner">
        <x-shop-logo class="storefront-header__logo text-lg" />

        <form action="{{ route('catalog.index') }}" method="get" role="search" class="storefront-search">
            <label class="sr-only" for="storefront-search">Buscar produtos, marcas ou categorias</label>
            <x-input id="storefront-search" type="search" name="q" :value="request('q', '')" class="storefront-search__input" placeholder="Buscar produtos, marcas ou categorias..." />
            <x-button type="submit" variant="secondary" class="storefront-search__submit" aria-label="Buscar produtos">
                <x-icon name="magnifying-glass" class="size-5" />
            </x-button>
        </form>

        <nav class="storefront-nav" aria-label="Navegação principal">
            <a href="{{ route('home') }}" @if(request()->routeIs('home')) aria-current="page" @endif>Início</a>
            <a href="{{ route('catalog.index') }}" @if(request()->routeIs('catalog.index')) aria-current="page" @endif>Categorias</a>
            <a href="{{ route('home') }}#ofertas">Ofertas</a>
            <a href="{{ route('catalog.index') }}">Vender</a>
        </nav>

        <div class="storefront-header__actions">
            <a href="{{ route('cart.show') }}" class="storefront-action storefront-cart" aria-label="Carrinho">
                <x-icon name="shopping-cart" class="size-5" />
                <span class="storefront-cart__dot" aria-hidden="true"></span>
            </a>
            @auth
                <a href="{{ route('dashboard') }}" class="storefront-action storefront-account" aria-label="Minha conta: {{ Auth::user()->name }}" title="{{ Auth::user()->name }}">
                    <span>Olá, {{ Str::before(Auth::user()->name, ' ') }}</span>
                </a>
            @else
                <a href="{{ route('login') }}" class="storefront-action storefront-account">Entrar</a>
            @endauth
        </div>
    </div>
</header>
