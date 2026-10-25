<section class="payment-panel ds-panel" aria-labelledby="payment-title">
    <div class="payment-heading"><h2 id="payment-title">Pagamento simulado</h2><x-badge variant="warning">Ambiente acadêmico</x-badge></div>
    <x-alert variant="info" class="payment-notice">
        <strong>Não use dados reais. Esta é uma simulação acadêmica, sem cobrança.</strong>
        <span>Use somente cartões fictícios: <code>4111 1111 1111 1111</code> aprova e <code>4000 0000 0000 0002</code> recusa. CVV de 3 dígitos (ou 4) é apenas validado e descartado.</span>
    </x-alert>
    <div class="payment-layout">
        <form wire:submit="pay" autocomplete="off" class="payment-form" aria-label="Dados fictícios de pagamento">
            <div class="payment-field payment-field--full"><x-label for="cardholder" value="Nome fictício no cartão" /><x-input id="cardholder" wire:model="cardholderName" type="text" maxlength="100" autocomplete="off" placeholder="Cliente Fictício" wire:loading.attr="disabled" wire:target="pay,cancel" aria-invalid="{{ $errors->has('cardholderName') ? 'true' : 'false' }}" aria-describedby="payment-error-cardholderName" /><div id="payment-error-cardholderName" aria-live="polite"><x-input-error for="cardholderName" /></div></div>
            <div class="payment-field payment-field--full"><x-label for="card-number" value="Número de cartão de teste" /><x-input id="card-number" wire:model="cardNumber" type="text" inputmode="numeric" maxlength="23" autocomplete="off" placeholder="4111 1111 1111 1111" wire:loading.attr="disabled" wire:target="pay,cancel" aria-invalid="{{ $errors->has('cardNumber') ? 'true' : 'false' }}" aria-describedby="payment-error-cardNumber" /><div id="payment-error-cardNumber" aria-live="polite"><x-input-error for="cardNumber" /></div></div>
            <div class="payment-field"><x-label for="expiry-month" value="Validade · mês" /><x-input id="expiry-month" wire:model="expiryMonth" type="number" min="1" max="12" autocomplete="off" wire:loading.attr="disabled" wire:target="pay,cancel" aria-invalid="{{ $errors->has('expiryMonth') || $errors->has('cardExpiry') ? 'true' : 'false' }}" aria-describedby="payment-error-expiryMonth payment-error-cardExpiry" /><div id="payment-error-expiryMonth" aria-live="polite"><x-input-error for="expiryMonth" /></div></div>
            <div class="payment-field"><x-label for="expiry-year" value="Validade · ano" /><x-input id="expiry-year" wire:model="expiryYear" type="number" min="{{ now()->year }}" max="{{ now()->addYears(15)->year }}" autocomplete="off" wire:loading.attr="disabled" wire:target="pay,cancel" aria-invalid="{{ $errors->has('expiryYear') || $errors->has('cardExpiry') ? 'true' : 'false' }}" aria-describedby="payment-error-expiryYear payment-error-cardExpiry" /><div id="payment-error-expiryYear" aria-live="polite"><x-input-error for="expiryYear" /></div></div>
            <div class="payment-field payment-field--full"><x-label for="cvv" value="CVV fictício" /><x-input id="cvv" wire:model="cvv" type="password" inputmode="numeric" maxlength="4" autocomplete="off" wire:loading.attr="disabled" wire:target="pay,cancel" aria-invalid="{{ $errors->has('cvv') ? 'true' : 'false' }}" aria-describedby="payment-cvv-help payment-error-cvv" /><p id="payment-cvv-help" class="ds-help">Use um código inventado de 3 ou 4 dígitos.</p><div id="payment-error-cvv" aria-live="polite"><x-input-error for="cvv" /></div></div>
            <div class="payment-field--full payment-errors" role="alert"><div id="payment-error-cardExpiry"><x-input-error for="cardExpiry" /></div><x-input-error for="payment" /><x-input-error for="order" /><x-input-error for="paymentKey" /></div>
            <x-button id="payment-submit" class="payment-field--full" type="submit" size="lg" wire:loading.attr="disabled" wire:target="pay,cancel"><span wire:loading.remove wire:target="pay">Simular pagamento de {{ \App\Domain\Catalog\Price::fromCents($order->total_cents)->brl() }}</span><span wire:loading wire:target="pay">Processando simulação…</span></x-button>
            <p class="payment-field--full checkout-loading" role="status"><span wire:loading wire:target="pay">Validando pagamento fictício…</span><span wire:loading wire:target="cancel">Cancelando pedido e liberando reserva…</span></p>
        </form>
        <aside class="payment-context" aria-labelledby="payment-order-title">
            <p class="checkout-eyebrow">Pedido criado</p><h3 id="payment-order-title">{{ $order->public_number }}</h3>
            <p>Seu pedido aguarda o pagamento fictício. O estoque já foi reservado na etapa anterior.</p>
            <dl><div><dt>Total a pagar na simulação</dt><dd>{{ \App\Domain\Catalog\Price::fromCents($order->total_cents)->brl() }}</dd></div></dl>
            <p>A aprovação conclui a compra fictícia. Na recusa ou no cancelamento, a reserva é liberada.</p>
            <x-button id="payment-cancel" variant="secondary" type="button" wire:click="cancel" wire:confirm="Cancelar o pedido e liberar a reserva?" wire:loading.attr="disabled" wire:target="pay,cancel">Cancelar pedido</x-button>
        </aside>
    </div>
</section>
