@once
    @push('auth-image-preload')
        <link rel="preload" as="image" href="{{ asset('images/login.png') }}" fetchpriority="high" media="(min-width: 1024px)">
    @endpush
@endonce
<aside class="auth-benefits" aria-label="Boas-vindas ao ShopPlus">
    <div>
        <div class="auth-login-brand">
            <x-shop-logo class="auth-benefits__logo" :accented="true" />
            <span class="auth-login-seal"><x-icon name="shopping-cart" />Loja Online</span>
        </div>
        <p class="auth-benefits__welcome">Sua próxima compra começa <span>aqui.</span></p>
        <p class="auth-benefits__description">Tudo o que você precisa em um só lugar, com praticidade para explorar produtos e acompanhar suas compras.</p>
        <ul class="auth-benefits__list">
            @foreach ([
                ['shopping-bag', 'Explore produtos', 'Encontre produtos no catálogo da loja.'],
                ['truck', 'Acompanhe pedidos', 'Consulte o status das suas compras.'],
                ['user', 'Gerencie sua conta', 'Atualize seus dados com segurança.'],
            ] as [$icon, $title, $description])
                <li><span class="auth-benefits__icon"><x-icon :name="$icon" /></span><span><strong>{{ $title }}</strong><span class="auth-benefits__detail">{{ $description }}</span></span></li>
            @endforeach
        </ul>
    </div>
    <div class="auth-login-art" aria-hidden="true"><img src="{{ asset('images/login.png') }}" width="1774" height="887" alt="" loading="eager" fetchpriority="high" decoding="sync"></div>
</aside>
