<a href="{{ route('cart.show') }}" class="storefront-action storefront-cart" aria-label="Carrinho{{ $quantity > 0 ? ', '.$quantity.' '.($quantity === 1 ? 'item' : 'itens') : '' }}">
    <span class="storefront-cart__icon" aria-hidden="true">
        <x-icon name="shopping-cart" class="size-5" />
        @if ($quantity > 0)
            <span class="storefront-cart__badge" aria-hidden="true">{{ $quantity }}</span>
        @endif
    </span>
</a>
