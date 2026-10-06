<div class="grid gap-6 md:grid-cols-2">
    <section class="ds-card">
        <div class="flex items-center gap-3"><span class="ds-badge ds-badge--info">1</span><h3 class="text-lg font-semibold text-slate-900">Itens e endereço</h3></div>
        @if (!$cart || $cart->items->isEmpty())
            <p class="mt-4 text-slate-600">O carrinho está vazio.</p>
        @else
            <ul class="mt-5 divide-y divide-slate-100">
                @foreach ($cart->items as $item)
                    <li class="flex justify-between gap-4 py-3 text-sm"><span class="text-slate-700">{{ $item->quantity }}× {{ $item->product->name }}</span><span class="font-medium text-slate-900">{{ \App\Domain\Catalog\Price::fromCents($item->product->price_cents * $item->quantity)->brl() }}</span></li>
                @endforeach
            </ul>
            <div class="mt-5">
                <x-label for="address" value="Endereço de entrega" />
                <select id="address" wire:model.live="addressId" class="ds-input">
                    <option value="">Selecione</option>
                    @foreach (Auth::user()->addresses as $address)<option value="{{ $address->id }}">{{ $address->label }} — {{ $address->postal_code_encrypted }}</option>@endforeach
                </select>
                @if (Auth::user()->addresses->isEmpty()) <a href="{{ route('personal-data.show') }}" class="mt-2 inline-block text-sm font-medium text-blue-700 hover:text-blue-900">Cadastre um endereço antes de continuar</a> @endif
                <x-input-error for="addressId" />
            </div>
        @endif
    </section>

    <section class="ds-card">
        <div class="flex items-center gap-3"><span class="ds-badge ds-badge--info">2</span><h3 class="text-lg font-semibold text-slate-900">Entrega</h3></div>
        <div class="mt-5 space-y-3">
            @foreach ($options as $key => $option)
                <label class="flex cursor-pointer justify-between gap-4 rounded-lg border border-slate-200 p-4 text-sm transition hover:border-blue-300 has-[:checked]:border-blue-600 has-[:checked]:bg-blue-50">
                    <span class="font-medium text-slate-700"><input type="radio" wire:model="shippingMethod" value="{{ $key }}" class="mr-2 border-slate-300 text-blue-600 focus:ring-blue-500">{{ $option['label'] }} (até {{ $option['days'] }} dias)</span>
                    <span class="whitespace-nowrap font-semibold text-slate-900">{{ \App\Domain\Catalog\Price::fromCents($option['price_cents'])->brl() }}</span>
                </label>
            @endforeach
        </div>
        <x-input-error for="checkout" />
        <x-input-error for="shippingMethod" />
        @if ($cart && $cart->items->isNotEmpty() && $options)
            <div class="mt-5 border-t border-slate-100 pt-5"><p class="text-sm text-slate-500">Subtotal recalculado</p><p class="mt-1 text-xl font-bold text-slate-900">{{ \App\Domain\Catalog\Price::fromCents($subtotal)->brl() }}</p></div>
            <x-button class="mt-5 w-full" type="button" wire:click="confirm" wire:loading.attr="disabled">Criar pedido e reservar estoque</x-button>
            <p class="ds-help">O pagamento fictício será feito na próxima tela. Nenhum dado de cartão é solicitado aqui.</p>
        @endif
    </section>
</div>
