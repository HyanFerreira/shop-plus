<div>
    @if (session('cart-status'))
        <x-alert variant="success" class="mb-5">{{ session('cart-status') }}</x-alert>
    @endif

    @if (!$cart || $cart->items->isEmpty())
        <div class="ds-card p-10 text-center">
            <p class="text-lg font-semibold text-slate-900">Seu carrinho está vazio.</p>
            <p class="mt-2 text-sm text-slate-500">Encontre produtos para continuar com sua compra.</p>
            <a href="{{ route('catalog.index') }}" class="ds-button ds-button--primary mt-6">Explorar catálogo</a>
        </div>
    @else
        <div class="ds-panel overflow-hidden">
            <div class="border-b border-slate-200 px-5 py-4 sm:px-6">
                <p class="font-semibold text-slate-900">Itens selecionados</p>
                <p class="mt-1 text-sm text-slate-500">Revise quantidades antes de avançar.</p>
            </div>

            @foreach ($cart->items as $item)
                <div class="flex flex-col gap-4 border-b border-slate-100 p-5 sm:flex-row sm:items-center sm:justify-between sm:px-6" wire:key="cart-item-{{ $item->id }}">
                    <div>
                        <a class="font-semibold text-slate-900 hover:text-blue-700" href="{{ route('catalog.show', $item->product->slug) }}">{{ $item->product->name }}</a>
                        <p class="mt-1 text-sm text-slate-500">{{ \App\Domain\Catalog\Price::fromCents($item->product->price_cents)->brl() }} cada</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <x-input aria-label="Quantidade de {{ $item->product->name }}" type="number" min="0" max="99" wire:model="quantities.{{ $item->id }}" class="w-20" />
                        <x-button size="sm" type="button" wire:click="updateItem({{ $item->id }})">Atualizar</x-button>
                        <x-button size="sm" variant="danger" type="button" wire:click="removeItem({{ $item->id }})">Remover</x-button>
                    </div>
                    @error('quantities.'.$item->id) <p class="ds-error sm:basis-full">{{ $message }}</p> @enderror
                </div>
            @endforeach

            <div class="flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                <div><p class="text-sm text-slate-500">Subtotal</p><p class="text-xl font-bold text-slate-900">{{ \App\Domain\Catalog\Price::fromCents($subtotal)->brl() }}</p></div>
                <a href="{{ route('checkout.show') }}" class="ds-button ds-button--primary">Ir para checkout</a>
            </div>
        </div>
    @endif
</div>
