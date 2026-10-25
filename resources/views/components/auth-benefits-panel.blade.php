<aside class="auth-benefits" aria-label="Boas-vindas ao ShopPlus">
    <div>
        <x-shop-logo class="auth-benefits__logo" />
        <p class="auth-benefits__welcome">Sua próxima compra<br>começa aqui.</p>
        <p class="auth-benefits__description">Tudo o que você precisa,<br>em um só lugar.</p>
        <ul class="auth-benefits__list">
            @foreach (['Explore nossos produtos', 'Acompanhe seus pedidos', 'Gerencie seus dados', 'Cuide da sua conta'] as $benefit)
                <li><x-icon name="check" stroke-width="2" /><span>{{ $benefit }}</span></li>
            @endforeach
        </ul>
    </div>
    <x-auth-illustration />
</aside>
