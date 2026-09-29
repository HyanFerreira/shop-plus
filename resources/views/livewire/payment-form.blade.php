<section class="rounded-lg bg-white p-6 shadow">
    <h3 class="text-lg font-semibold">Pagamento simulado</h3>
    <div class="mt-3 rounded-md bg-blue-50 p-3 text-sm text-blue-900">
        Use somente cartões fictícios: <strong>4111 1111 1111 1111</strong> aprova e <strong>4000 0000 0000 0002</strong> recusa. Qualquer CVV de 3 dígitos é apenas validado e descartado.
    </div>
    <form wire:submit="pay" autocomplete="off" class="mt-5 grid gap-4 sm:grid-cols-2">
        <div class="sm:col-span-2"><label for="cardholder" class="block text-sm font-medium">Nome fictício no cartão</label><input id="cardholder" wire:model="cardholderName" type="text" maxlength="100" autocomplete="off" class="mt-1 w-full rounded-md border-gray-300"></div>
        <div class="sm:col-span-2"><label for="card-number" class="block text-sm font-medium">Número fictício</label><input id="card-number" wire:model="cardNumber" type="text" inputmode="numeric" maxlength="23" autocomplete="off" class="mt-1 w-full rounded-md border-gray-300"></div>
        <div><label for="expiry-month" class="block text-sm font-medium">Mês</label><input id="expiry-month" wire:model="expiryMonth" type="number" min="1" max="12" class="mt-1 w-full rounded-md border-gray-300"></div>
        <div><label for="expiry-year" class="block text-sm font-medium">Ano</label><input id="expiry-year" wire:model="expiryYear" type="number" min="{{ now()->year }}" max="{{ now()->addYears(15)->year }}" class="mt-1 w-full rounded-md border-gray-300"></div>
        <div><label for="cvv" class="block text-sm font-medium">CVV fictício</label><input id="cvv" wire:model="cvv" type="password" inputmode="numeric" maxlength="4" autocomplete="off" class="mt-1 w-full rounded-md border-gray-300"></div>
        <div class="flex items-end"><button type="submit" wire:loading.attr="disabled" class="w-full rounded-md bg-indigo-600 px-5 py-3 font-semibold text-white disabled:opacity-50">Pagar {{ \App\Domain\Catalog\Price::fromCents($order->total_cents)->brl() }}</button></div>
        @foreach (['cardholderName', 'cardNumber', 'cvv', 'expiryMonth', 'expiryYear', 'payment'] as $field) @error($field)<p class="text-sm text-red-600 sm:col-span-2">{{ $message }}</p>@enderror @endforeach
    </form>
    <button type="button" wire:click="cancel" wire:confirm="Cancelar o pedido e liberar a reserva?" class="mt-5 text-sm text-red-700">Cancelar pedido</button>
</section>
