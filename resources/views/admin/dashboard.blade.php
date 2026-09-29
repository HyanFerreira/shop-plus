<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Administração') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid gap-4 md:grid-cols-4">@foreach(['paid_sales_cents' => 'Vendas', 'pending_orders' => 'Pedidos pendentes', 'open_purchases' => 'Compras abertas', 'low_stock' => 'Estoque baixo'] as $key => $label)<div class="rounded-lg bg-white p-6 shadow"><p class="text-sm text-gray-500">{{ $label }}</p><p class="mt-2 text-2xl font-bold">{{ $key === 'paid_sales_cents' ? \App\Domain\Catalog\Price::fromCents($metrics[$key])->brl() : $metrics[$key] }}</p></div>@endforeach</div>
        </div>
    </div>
</x-app-layout>
