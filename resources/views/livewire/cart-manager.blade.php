<div>
    @if (session('cart-status')) <div class="mb-4 rounded-md bg-green-50 p-4 text-green-800">{{ session('cart-status') }}</div> @endif
    @if (!$cart || $cart->items->isEmpty())
        <div class="rounded-lg bg-white p-8 text-center shadow"><p class="text-gray-600">Seu carrinho está vazio.</p><a href="{{ route('catalog.index') }}" class="mt-4 inline-block text-indigo-700">Explorar catálogo</a></div>
    @else
        <div class="overflow-hidden rounded-lg bg-white shadow">
            @foreach ($cart->items as $item)
                <div class="flex flex-col gap-4 border-b p-5 sm:flex-row sm:items-center sm:justify-between" wire:key="cart-item-{{ $item->id }}">
                    <div><a class="font-semibold text-gray-900" href="{{ route('catalog.show', $item->product->slug) }}">{{ $item->product->name }}</a><p class="text-sm text-gray-500">{{ \App\Domain\Catalog\Price::fromCents($item->product->price_cents)->brl() }} cada</p></div>
                    <div class="flex items-center gap-2">
                        <input aria-label="Quantidade de {{ $item->product->name }}" type="number" min="0" max="99" wire:model="quantities.{{ $item->id }}" class="w-20 rounded-md border-gray-300">
                        <button wire:click="updateItem({{ $item->id }})" class="rounded bg-gray-800 px-3 py-2 text-sm text-white">Atualizar</button>
                        <button wire:click="removeItem({{ $item->id }})" class="px-3 py-2 text-sm text-red-700">Remover</button>
                    </div>
                    @error('quantities.'.$item->id) <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            @endforeach
            <div class="flex items-center justify-between p-5"><p class="text-lg font-semibold">Subtotal: {{ \App\Domain\Catalog\Price::fromCents($subtotal)->brl() }}</p><a href="{{ route('checkout.show') }}" class="rounded-md bg-indigo-600 px-5 py-3 font-semibold text-white">Ir para checkout</a></div>
        </div>
    @endif
</div>
