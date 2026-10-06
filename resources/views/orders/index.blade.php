<x-app-layout>
    <x-slot name="header"><h2 class="text-xl font-bold tracking-tight text-slate-900">Meus pedidos</h2></x-slot>
    <div class="py-10"><div class="ds-container max-w-5xl">
        <div class="ds-panel overflow-hidden">
            @forelse ($orders as $order)
                <a href="{{ route('orders.show', $order->public_number) }}" class="flex flex-col gap-3 border-b border-slate-100 p-5 transition hover:bg-slate-50 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                    <div><p class="font-semibold text-slate-900">{{ $order->public_number }}</p><p class="mt-1 text-sm text-slate-500">{{ $order->created_at->format('d/m/Y H:i') }}</p></div>
                    <div class="sm:text-right"><x-badge variant="info">{{ $order->status->value }}</x-badge><p class="mt-2 font-semibold text-slate-900">{{ \App\Domain\Catalog\Price::fromCents($order->total_cents)->brl() }}</p></div>
                </a>
            @empty
                <div class="p-10 text-center"><p class="font-semibold text-slate-900">Nenhum pedido encontrado.</p><a href="{{ route('catalog.index') }}" class="mt-3 inline-block text-sm font-medium text-blue-700 hover:text-blue-900">Explorar catálogo</a></div>
            @endforelse
        </div>
        <div class="mt-5">{{ $orders->links() }}</div>
    </div></div>
</x-app-layout>
