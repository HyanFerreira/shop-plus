<nav aria-label="Navegação da conta">
    <a href="{{ route('dashboard') }}" @if(request()->routeIs('dashboard')) aria-current="page" @endif><x-icon name="home" class="size-5" />Visão geral</a>
    <a href="{{ route('profile.show') }}" @if(request()->routeIs('profile.*', 'personal-data.*')) aria-current="page" @endif><x-icon name="user" class="size-5" />Perfil</a>
    <a href="{{ route('orders.index') }}" @if(request()->routeIs('orders.*')) aria-current="page" @endif><x-icon name="shopping-bag" class="size-5" />Pedidos</a>
    <a href="{{ route('favorites.index') }}" @if(request()->routeIs('favorites.*')) aria-current="page" @endif><x-icon name="heart" class="size-5" />Favoritos</a>
    <hr class="customer-navigation__divider">
    <a href="{{ route('users.index') }}" @if(request()->routeIs('users.*', 'monitoring.*')) aria-current="page" @endif><x-icon name="users" class="size-5" />Ver usuários</a>
</nav>
<hr class="customer-navigation__divider">
<form method="POST" action="{{ route('logout') }}" class="customer-navigation__logout">@csrf<button type="submit"><x-arrow />Sair da conta</button></form>
