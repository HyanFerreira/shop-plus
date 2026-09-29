<div class="grid gap-6 md:grid-cols-2">
    <section class="rounded-lg bg-white p-6 shadow">
        <h3 class="text-lg font-semibold">1. Itens e endereço</h3>
        @if (!$cart || $cart->items->isEmpty())
            <p class="mt-4 text-gray-600">O carrinho está vazio.</p>
        @else
            <ul class="mt-4 divide-y">@foreach ($cart->items as $item)<li class="flex justify-between py-2"><span>{{ $item->quantity }}× {{ $item->product->name }}</span><span>{{ \App\Domain\Catalog\Price::fromCents($item->product->price_cents * $item->quantity)->brl() }}</span></li>@endforeach</ul>
            <label for="address" class="mt-5 block text-sm font-medium">Endereço de entrega</label>
            <select id="address" wire:model.live="addressId" class="mt-1 w-full rounded-md border-gray-300">
                <option value="">Selecione</option>
                @foreach (Auth::user()->addresses as $address)<option value="{{ $address->id }}">{{ $address->label }} — {{ $address->postal_code_encrypted }}</option>@endforeach
            </select>
            @if (Auth::user()->addresses->isEmpty()) <a href="{{ route('personal-data.show') }}" class="mt-2 inline-block text-sm text-indigo-700">Cadastre um endereço antes de continuar</a> @endif
        @endif
    </section>
    <section class="rounded-lg bg-white p-6 shadow">
        <h3 class="text-lg font-semibold">2. Entrega</h3>
        <div class="mt-4 space-y-3">@foreach ($options as $key => $option)<label class="flex cursor-pointer justify-between rounded-md border p-3"><span><input type="radio" wire:model="shippingMethod" value="{{ $key }}" class="mr-2">{{ $option['label'] }} (até {{ $option['days'] }} dias)</span><span>{{ \App\Domain\Catalog\Price::fromCents($option['price_cents'])->brl() }}</span></label>@endforeach</div>
        @error('checkout') <p class="mt-3 text-sm text-red-600">{{ $message }}</p> @enderror
        @error('addressId') <p class="mt-3 text-sm text-red-600">{{ $message }}</p> @enderror
        @error('shippingMethod') <p class="mt-3 text-sm text-red-600">{{ $message }}</p> @enderror
        @if ($cart && $cart->items->isNotEmpty() && $options)
            <p class="mt-5 text-sm text-gray-600">Subtotal recalculado: {{ \App\Domain\Catalog\Price::fromCents($subtotal)->brl() }}</p>
            <button wire:click="confirm" wire:loading.attr="disabled" class="mt-4 w-full rounded-md bg-indigo-600 px-5 py-3 font-semibold text-white disabled:opacity-50">Criar pedido e reservar estoque</button>
            <p class="mt-3 text-xs text-gray-500">O pagamento fictício será feito na próxima tela. Nenhum dado de cartão é solicitado aqui.</p>
        @endif
    </section>
</div>
