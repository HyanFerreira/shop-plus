<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-bold tracking-tight text-slate-900 leading-tight">
            {{ __('Administração') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="ds-container">
            <div class="grid gap-4 md:grid-cols-4">@foreach(['paid_sales_cents' => 'Vendas', 'pending_orders' => 'Pedidos pendentes', 'open_purchases' => 'Compras abertas', 'low_stock' => 'Estoque baixo'] as $key => $label)<div class="ds-card"><p class="text-sm text-slate-500">{{ $label }}</p><p class="mt-2 text-2xl font-bold text-slate-900">{{ $key === 'paid_sales_cents' ? \App\Domain\Catalog\Price::fromCents($metrics[$key])->brl() : $metrics[$key] }}</p></div>@endforeach</div>
        </div>
    </div>
</x-app-layout>
