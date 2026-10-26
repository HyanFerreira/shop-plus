@props(['detailed' => false])
<aside class="auth-benefits" aria-label="Boas-vindas ao ShopPlus">
    <div>
        @if ($detailed)
            <div class="auth-login-brand">
                <x-shop-logo class="auth-benefits__logo" :accented="true" />
                <span class="auth-login-seal"><x-icon name="shopping-cart" />Loja Online</span>
            </div>
            <p class="auth-benefits__welcome">Sua próxima compra começa <span>aqui.</span></p>
            <p class="auth-benefits__description">Tudo o que você precisa em um só lugar, com praticidade para explorar produtos e acompanhar suas compras.</p>
        @else
            <x-shop-logo class="auth-benefits__logo" />
            <p class="auth-benefits__welcome">Sua próxima compra<br>começa aqui.</p>
            <p class="auth-benefits__description">Tudo o que você precisa,<br>em um só lugar.</p>
        @endif
        <ul class="auth-benefits__list">
            @if ($detailed)
                @foreach ([
                    ['shopping-bag', 'Explore produtos', 'Encontre produtos no catálogo da loja.'],
                    ['truck', 'Acompanhe pedidos', 'Consulte o status das suas compras.'],
                    ['user', 'Gerencie sua conta', 'Atualize seus dados com segurança.'],
                ] as [$icon, $title, $description])
                    <li><span class="auth-benefits__icon"><x-icon :name="$icon" /></span><span><strong>{{ $title }}</strong><span class="auth-benefits__detail">{{ $description }}</span></span></li>
                @endforeach
            @else
            @foreach (['Explore nossos produtos', 'Acompanhe seus pedidos', 'Gerencie seus dados', 'Cuide da sua conta'] as $benefit)
                <li><x-icon name="check" stroke-width="2" /><span>{{ $benefit }}</span></li>
            @endforeach
            @endif
        </ul>
    </div>
    @if ($detailed)
        <div class="auth-login-art" aria-hidden="true"><img src="{{ asset('images/login.png') }}" width="1774" height="887" alt="" fetchpriority="high" decoding="async"></div>
    @else
        <x-auth-illustration />
    @endif
</aside>
