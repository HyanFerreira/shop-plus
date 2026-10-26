<div class="product-quick-add" wire:loading.attr="aria-busy" wire:target="add">
    @php($available = max(0, ($product->inventoryItem?->on_hand ?? 0) - ($product->inventoryItem?->reserved ?? 0)))
    @auth
        <form wire:submit="add">
            <x-button type="submit" class="product-quick-add__button" wire:loading.attr="disabled" wire:target="add"
                :disabled="$available === 0" aria-label="{{ $available > 0 ? 'Adicionar ao carrinho: ' : 'Indisponível: ' }}{{ $product->name }}">
                <x-icon name="shopping-cart" class="size-5" />
                @if($available > 0)
                    <span class="product-quick-add__label">Adicionar<span class="product-quick-add__long-label"> ao carrinho</span></span>
                @else
                    <span class="product-quick-add__unavailable">Indisponível</span>
                @endif
            </x-button>
        </form>
    @else
        @if($available > 0)
            <a href="{{ route('login') }}" class="ds-button ds-button--primary product-quick-add__button" aria-label="Entre para adicionar ao carrinho: {{ $product->name }}">
                <x-icon name="shopping-cart" class="size-5" /><span class="product-quick-add__label">Adicionar<span class="product-quick-add__long-label"> ao carrinho</span></span>
            </a>
        @else
            <x-button type="button" class="product-quick-add__button" disabled aria-label="Indisponível: {{ $product->name }}"><x-icon name="shopping-cart" class="size-5" /><span class="product-quick-add__unavailable">Indisponível</span></x-button>
        @endif
    @endauth
    <span class="sr-only" role="status" aria-atomic="true">{{ $status }}</span>
    <div class="product-quick-add__errors" role="status" aria-atomic="true">
        <x-input-error for="quantity" />
        <x-input-error for="product" />
    </div>
</div>
