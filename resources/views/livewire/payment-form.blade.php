<section class="ds-card">
    <div class="flex items-center justify-between gap-4"><h3 class="text-lg font-semibold text-slate-900">Pagamento simulado</h3><x-badge variant="warning">Ambiente de teste</x-badge></div>
    <x-alert variant="info" class="mt-4">Use somente cartões fictícios: <strong>4111 1111 1111 1111</strong> aprova e <strong>4000 0000 0000 0002</strong> recusa. Qualquer CVV de 3 dígitos é apenas validado e descartado.</x-alert>
    <form wire:submit="pay" autocomplete="off" class="mt-5 grid gap-4 sm:grid-cols-2">
        <div class="sm:col-span-2"><x-label for="cardholder" value="Nome fictício no cartão" /><x-input id="cardholder" wire:model="cardholderName" type="text" maxlength="100" autocomplete="off" /></div>
        <div class="sm:col-span-2"><x-label for="card-number" value="Número fictício" /><x-input id="card-number" wire:model="cardNumber" type="text" inputmode="numeric" maxlength="23" autocomplete="off" /></div>
        <div><x-label for="expiry-month" value="Mês" /><x-input id="expiry-month" wire:model="expiryMonth" type="number" min="1" max="12" /></div>
        <div><x-label for="expiry-year" value="Ano" /><x-input id="expiry-year" wire:model="expiryYear" type="number" min="{{ now()->year }}" max="{{ now()->addYears(15)->year }}" /></div>
        <div><x-label for="cvv" value="CVV fictício" /><x-input id="cvv" wire:model="cvv" type="password" inputmode="numeric" maxlength="4" autocomplete="off" /></div>
        <div class="flex items-end"><x-button class="w-full" type="submit" wire:loading.attr="disabled">Pagar {{ \App\Domain\Catalog\Price::fromCents($order->total_cents)->brl() }}</x-button></div>
        @foreach (['cardholderName', 'cardNumber', 'cvv', 'expiryMonth', 'expiryYear', 'payment'] as $field) <x-input-error :for="$field" class="sm:col-span-2" /> @endforeach
    </form>
    <x-button variant="danger" size="sm" type="button" wire:click="cancel" wire:confirm="Cancelar o pedido e liberar a reserva?" class="mt-5">Cancelar pedido</x-button>
</section>
