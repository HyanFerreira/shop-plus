<div class="product-purchase">
    @php
        $available = max(0, ($product->inventoryItem?->on_hand ?? 0) - ($product->inventoryItem?->reserved ?? 0));
        $quantityLimit = min(99, $available);
    @endphp
    <form wire:submit="add" class="product-purchase__form">
        <div class="product-quantity-field">
            <x-label for="product-quantity" value="Quantidade" />
            <div class="product-quantity">
                <x-button type="button" variant="secondary" size="lg" wire:click="decrement" wire:loading.attr="disabled" aria-label="Diminuir quantidade" :disabled="$available === 0 || $quantity <= 1" x-bind:disabled="$wire.quantity <= 1 || {{ $available === 0 ? 'true' : 'false' }}">−</x-button>
                <x-input id="product-quantity" type="number" inputmode="numeric" min="1" max="{{ max(1, $quantityLimit) }}" wire:model="quantity" :disabled="$available === 0" aria-describedby="product-quantity-help product-purchase-errors" aria-invalid="{{ $errors->has('quantity') ? 'true' : 'false' }}" />
                <x-button id="product-quantity-increment" type="button" variant="secondary" size="lg" wire:click="increment" wire:loading.attr="disabled" aria-label="Aumentar quantidade" :disabled="$available === 0 || $quantity >= $quantityLimit" x-bind:disabled="$wire.quantity >= {{ $quantityLimit }}">+</x-button>
            </div>
        </div>
        <x-button id="product-add-submit" type="submit" size="lg" wire:loading.attr="disabled" :disabled="$available === 0">
            <x-icon name="shopping-cart" class="size-5" />
            <span wire:loading.remove wire:target="add">Adicionar ao carrinho</span><span wire:loading wire:target="add">Adicionando…</span>
        </x-button>
    </form>
    <p id="product-quantity-help" class="ds-help">{{ $available > 0 ? 'Disponibilidade confirmada ao adicionar ao carrinho.' : 'Este produto está sem estoque disponível.' }}</p>
    <div id="product-purchase-errors" aria-live="polite">
        <x-input-error for="quantity" />
        <x-input-error for="product" />
    </div>
</div>
