<x-app-layout>
    @php
        $pendingPayment = $order->status === \App\Enums\OrderStatus::PendingPayment;
        $address = $order->address_snapshot_encrypted ?? [];
        $shippingLabel = match ($order->shipping_method) { 'economy' => 'Econômica', 'express' => 'Expressa', 'pickup' => 'Retirada', default => $order->shipping_method };
    @endphp
    <div class="orders-page"><div class="ds-storefront-container orders-container {{ $pendingPayment ? 'payment-page' : '' }}">
        <a href="{{ route('orders.index') }}" class="order-back-link">← Voltar para pedidos</a>
        <div class="orders-heading"><p class="order-eyebrow">{{ $pendingPayment ? 'Etapa 2 de 2' : 'Detalhes da compra' }}</p><h1>{{ $pendingPayment ? 'Pagamento do pedido' : 'Detalhes do pedido' }}</h1><p>Pedido <strong>{{ $order->public_number }}</strong> · <time datetime="{{ ($order->placed_at ?? $order->created_at)->toIso8601String() }}">{{ ($order->placed_at ?? $order->created_at)->format('d/m/Y H:i') }}</time></p></div>
        @if(session('payment-status'))<x-alert variant="info" class="order-flash">{{ session('payment-status') }}</x-alert>@endif
        @if($pendingPayment)<div class="order-payment"><livewire:payment-form :order="$order" /></div>@endif
        <div class="customer-layout">
            <div class="orders-content orders-detail">
                <section class="order-panel order-overview ds-panel" aria-labelledby="order-status-title"><div><p class="order-eyebrow">Andamento da compra</p><h2 id="order-status-title">Status do pedido</h2><p>{{ $order->public_number }}</p></div><x-order-status :status="$order->status" />
                    @if($pendingPayment)<p class="order-overview__note">Aguardando pagamento fictício. Use o formulário acima para pagar ou cancelar o pedido.</p>@elseif($order->status === \App\Enums\OrderStatus::Cancelled)<p class="order-overview__note">Este pedido foi cancelado. Consulte o histórico e os registros de pagamento abaixo.</p>@elseif($order->status === \App\Enums\OrderStatus::Refunded)<p class="order-overview__note">Este pedido foi reembolsado na simulação. O andamento logístico é informado separadamente.</p>@endif
                </section>
                <x-shipment-tracking :shipment="$order->shipment" />
                <section class="order-panel ds-panel" aria-labelledby="order-items-title"><div class="order-section-heading"><h2 id="order-items-title">Itens do pedido</h2><p>{{ $order->items->sum('quantity') }} {{ $order->items->sum('quantity') === 1 ? 'unidade' : 'unidades' }}</p></div>
                    <ul class="order-items">
                        @forelse($order->items as $item)
                            <li><x-order-item-image :item="$item" /><div class="order-item-description"><h3>{{ $item->product_name }}</h3><p>SKU: {{ $item->sku }}</p><p>Quantidade: {{ $item->quantity }} · Unitário: {{ \App\Domain\Catalog\Price::fromCents($item->unit_price_cents)->brl() }}</p></div><p class="order-item-total"><span>Total do item</span><strong>{{ \App\Domain\Catalog\Price::fromCents($item->total_cents)->brl() }}</strong></p></li>
                        @empty<li><p>Nenhum item registrado neste pedido.</p></li>@endforelse
                    </ul>
                    <dl class="order-totals"><div><dt>Subtotal registrado</dt><dd>{{ \App\Domain\Catalog\Price::fromCents($order->subtotal_cents)->brl() }}</dd></div><div><dt>Entrega</dt><dd>{{ \App\Domain\Catalog\Price::fromCents($order->shipping_cents)->brl() }}</dd></div><div class="order-totals__grand"><dt>Total do pedido</dt><dd>{{ \App\Domain\Catalog\Price::fromCents($order->total_cents)->brl() }}</dd></div></dl>
                </section>
                <div class="order-detail-columns">
                    <section class="order-panel ds-panel" aria-labelledby="order-address-title"><h2 id="order-address-title">{{ $order->shipping_method === 'pickup' ? 'Endereço informado' : 'Endereço de entrega' }}</h2>
                        @if($address)<address class="order-address"><strong>{{ $address['recipient'] ?? '' }}</strong><span>{{ $address['street'] ?? '' }}@if(isset($address['number'])), {{ $address['number'] }}@endif</span>@if(!empty($address['complement']))<span>{{ $address['complement'] }}</span>@endif @if(!empty($address['district']))<span>{{ $address['district'] }}</span>@endif<span>{{ $address['city'] ?? '' }}/{{ $address['state'] ?? '' }}</span><span>CEP {{ $address['postal_code'] ?? '' }}</span></address>@else<p class="order-secondary">Endereço não registrado.</p>@endif
                        <div class="order-delivery-method"><h3>Modalidade: {{ $shippingLabel }}</h3><p>Prazo contratado: {{ $order->shipping_days }} {{ $order->shipping_days === 1 ? 'dia' : 'dias' }}.</p><p>Prazo registrado na compra; não representa uma data de entrega confirmada.</p>@if($order->shipping_method === 'pickup')<p>Modalidade de retirada. Nenhum local ou instrução adicional foi registrado nesta tela.</p>@endif</div>
                    </section>
                    <section class="order-panel ds-panel" aria-labelledby="order-payment-title"><h2 id="order-payment-title">Registros do pagamento</h2><p class="order-secondary">Pagamento fictício, sem cobrança real.</p><ul class="order-payments">
                        @forelse($order->payments->sortBy('created_at') as $payment)<li><x-order-status :status="$payment->status" /><p>{{ $payment->brand }} final {{ $payment->last_four }}</p><p>Valor registrado: {{ \App\Domain\Catalog\Price::fromCents($payment->amount_cents)->brl() }}</p>@if($payment->processed_at)<time datetime="{{ $payment->processed_at->toIso8601String() }}">{{ $payment->processed_at->format('d/m/Y H:i') }}</time>@endif</li>@empty<li>Nenhum pagamento registrado.@if($pendingPayment) O pedido aguarda a simulação acima.@endif</li>@endforelse
                    </ul></section>
                </div>
                <section class="order-panel ds-panel" aria-labelledby="order-history-title"><div class="order-section-heading"><div><p class="order-eyebrow">Andamento da compra</p><h2 id="order-history-title">Histórico do pedido</h2></div></div><ol class="order-event-list">
                    @forelse($order->statusHistories->sort(fn ($a, $b) => ($a->created_at <=> $b->created_at) ?: ($a->id <=> $b->id)) as $history)<li><time datetime="{{ $history->created_at->toIso8601String() }}">{{ $history->created_at->format('d/m/Y H:i') }}</time><div><x-order-status :status="$history->to_status" />@if($history->note)<p>{{ $history->note }}</p>@endif</div></li>@empty<li><p>Nenhuma alteração de status registrada.</p></li>@endforelse
                </ol></section>
            </div>
        </div>
    </div></div>
</x-app-layout>