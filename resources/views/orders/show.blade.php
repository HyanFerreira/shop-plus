<x-app-layout>
    <x-slot name="header"><h2 class="text-xl font-semibold text-gray-800">Pedido {{ $order->public_number }}</h2></x-slot>
    <div class="py-10"><div class="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">
        @if (session('payment-status')) <div class="rounded-lg bg-blue-50 p-5 text-blue-900">{{ session('payment-status') }}</div> @endif
        <div class="rounded-lg p-5 {{ $order->status === \App\Enums\OrderStatus::Paid ? 'bg-green-50 text-green-900' : ($order->status === \App\Enums\OrderStatus::Cancelled ? 'bg-gray-100 text-gray-800' : 'bg-amber-50 text-amber-900') }}">Estado: {{ $order->status->value }}.</div>
        <section class="rounded-lg bg-white p-6 shadow"><h3 class="font-semibold">Itens</h3><ul class="mt-3 divide-y">@foreach ($order->items as $item)<li class="flex justify-between py-3"><span>{{ $item->quantity }}× {{ $item->product_name }} <small class="text-gray-500">{{ $item->sku }}</small></span><span>{{ \App\Domain\Catalog\Price::fromCents($item->total_cents)->brl() }}</span></li>@endforeach</ul><div class="mt-4 text-right"><p>Frete: {{ \App\Domain\Catalog\Price::fromCents($order->shipping_cents)->brl() }}</p><p class="text-lg font-bold">Total: {{ \App\Domain\Catalog\Price::fromCents($order->total_cents)->brl() }}</p></div></section>
        <section class="rounded-lg bg-white p-6 shadow"><h3 class="font-semibold">Entrega</h3><p class="mt-2">{{ $order->address_snapshot_encrypted['recipient'] }} — {{ $order->address_snapshot_encrypted['street'] }}, {{ $order->address_snapshot_encrypted['number'] }}</p><p>{{ $order->address_snapshot_encrypted['city'] }}/{{ $order->address_snapshot_encrypted['state'] }} — {{ $order->address_snapshot_encrypted['postal_code'] }}</p><p class="mt-2 text-sm text-gray-500">Modalidade {{ $order->shipping_method }}, prazo estimado de {{ $order->shipping_days }} dias.</p></section>
        @if ($order->status === \App\Enums\OrderStatus::PendingPayment)
            <livewire:payment-form :order="$order" />
        @elseif ($order->payments->isNotEmpty())
            <section class="rounded-lg bg-white p-6 shadow"><h3 class="font-semibold">Pagamento</h3>@foreach ($order->payments as $payment)<p class="mt-2">{{ $payment->brand }} final {{ $payment->last_four }} — {{ $payment->status->value }}</p>@endforeach</section>
        @endif
    </div></div>
</x-app-layout>
