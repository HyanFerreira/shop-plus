<x-app-layout>
    <x-slot name="header"><h2 class="text-xl font-bold tracking-tight text-slate-900">Pedido {{ $order->public_number }}</h2></x-slot>
    <div class="py-10"><div class="ds-container max-w-4xl space-y-6">
        @if (session('payment-status')) <x-alert variant="info">{{ session('payment-status') }}</x-alert> @endif
        <div class="ds-card flex flex-wrap items-center justify-between gap-3"><div><p class="text-sm text-slate-500">Status do pedido</p><p class="mt-1 font-semibold text-slate-900">{{ $order->status->value }}</p></div><x-badge variant="{{ $order->status === \App\Enums\OrderStatus::Paid ? 'success' : ($order->status === \App\Enums\OrderStatus::Cancelled ? 'neutral' : 'warning') }}">{{ $order->status->value }}</x-badge></div>
        <section class="ds-card"><h3 class="font-semibold text-slate-900">Itens</h3><ul class="mt-3 divide-y divide-slate-100">@foreach ($order->items as $item)<li class="flex justify-between gap-4 py-3"><span class="text-slate-700">{{ $item->quantity }}× {{ $item->product_name }} <small class="text-slate-500">{{ $item->sku }}</small></span><span class="font-medium text-slate-900">{{ \App\Domain\Catalog\Price::fromCents($item->total_cents)->brl() }}</span></li>@endforeach</ul><div class="mt-4 text-right text-slate-700"><p>Frete: {{ \App\Domain\Catalog\Price::fromCents($order->shipping_cents)->brl() }}</p><p class="mt-1 text-lg font-bold text-slate-900">Total: {{ \App\Domain\Catalog\Price::fromCents($order->total_cents)->brl() }}</p></div></section>
        <section class="ds-card"><h3 class="font-semibold text-slate-900">Entrega</h3><p class="mt-3 text-slate-700">{{ $order->address_snapshot_encrypted['recipient'] }} — {{ $order->address_snapshot_encrypted['street'] }}, {{ $order->address_snapshot_encrypted['number'] }}</p><p class="text-slate-700">{{ $order->address_snapshot_encrypted['city'] }}/{{ $order->address_snapshot_encrypted['state'] }} — {{ $order->address_snapshot_encrypted['postal_code'] }}</p><p class="mt-3 text-sm text-slate-500">Modalidade {{ $order->shipping_method }}, prazo estimado de {{ $order->shipping_days }} dias.</p></section>
        @if ($order->status === \App\Enums\OrderStatus::PendingPayment)
            <livewire:payment-form :order="$order" />
        @elseif ($order->payments->isNotEmpty())
            <section class="ds-card"><h3 class="font-semibold text-slate-900">Pagamento</h3>@foreach ($order->payments as $payment)<p class="mt-3 text-slate-700">{{ $payment->brand }} final {{ $payment->last_four }} — {{ $payment->status->value }}</p>@endforeach</section>
        @endif
        @if ($order->shipment)
            <section class="ds-card"><h3 class="font-semibold text-slate-900">Rastreio fictício {{ $order->shipment->tracking_code }}</h3><ol class="mt-5 space-y-4">@foreach ($order->shipment->events as $event)<li class="border-l-2 border-blue-300 pl-4"><p class="font-medium text-slate-900">{{ $event->to_status->value }}</p><p class="mt-1 text-sm text-slate-500">{{ $event->created_at->format('d/m/Y H:i') }}@if($event->note) · {{ $event->note }}@endif</p></li>@endforeach</ol></section>
        @endif
    </div></div>
</x-app-layout>
