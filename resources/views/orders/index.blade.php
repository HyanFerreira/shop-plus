<x-app-layout>
    <x-slot name="header"><h2 class="text-xl font-semibold text-gray-800">Meus pedidos</h2></x-slot>
    <div class="py-10"><div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
        <div class="overflow-hidden rounded-lg bg-white shadow">
            @forelse ($orders as $order)
                <a href="{{ route('orders.show', $order->public_number) }}" class="flex flex-col gap-2 border-b p-5 hover:bg-gray-50 sm:flex-row sm:items-center sm:justify-between">
                    <div><p class="font-semibold">{{ $order->public_number }}</p><p class="text-sm text-gray-500">{{ $order->created_at->format('d/m/Y H:i') }}</p></div>
                    <div class="sm:text-right"><p>{{ $order->status->value }}</p><p class="font-semibold">{{ \App\Domain\Catalog\Price::fromCents($order->total_cents)->brl() }}</p></div>
                </a>
            @empty
                <p class="p-8 text-center text-gray-600">Nenhum pedido encontrado.</p>
            @endforelse
        </div>
        <div class="mt-5">{{ $orders->links() }}</div>
    </div></div>
</x-app-layout>
