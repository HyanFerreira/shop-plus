<x-app-layout>
    <div class="orders-page"><div class="ds-storefront-container orders-container">
        <div class="customer-layout">
            <div class="orders-content">
                <div class="orders-heading"><p class="order-eyebrow">Minha conta</p><h1>Meus pedidos</h1><p>Consulte suas compras e acompanhe cada entrega.</p></div>
                <div class="orders-list">
                    @forelse ($orders as $order)
                        <article class="order-list-card ds-panel" aria-labelledby="order-{{ $order->id }}">
                            <div class="order-list-card__heading"><div><h2 id="order-{{ $order->id }}">Pedido {{ $order->public_number }}</h2><p><time datetime="{{ ($order->placed_at ?? $order->created_at)->toIso8601String() }}">{{ ($order->placed_at ?? $order->created_at)->format('d/m/Y H:i') }}</time></p></div><div><span class="sr-only">Status do pedido: </span><x-order-status :status="$order->status" /></div></div>
                            <div class="order-list-card__body">
                                <x-order-item-image :item="$order->items->first()" />
                                <div class="order-list-card__products"><p>{{ $order->items->count() }} {{ $order->items->count() === 1 ? 'item' : 'itens' }} · {{ $order->items->sum('quantity') }} {{ $order->items->sum('quantity') === 1 ? 'unidade' : 'unidades' }}</p>
                                    @if($order->items->isNotEmpty())<h3>{{ $order->items->first()->product_name }}</h3>@if($order->items->count() > 1)<p>e mais {{ $order->items->count() - 1 }} {{ $order->items->count() === 2 ? 'item' : 'itens' }}</p>@endif @else<p>Nenhum item registrado neste pedido.</p>@endif
                                </div>
                                <div class="order-list-card__total"><p>Total do pedido</p><strong>{{ \App\Domain\Catalog\Price::fromCents($order->total_cents)->brl() }}</strong></div>
                            </div>
                            <div class="order-list-card__footer"><p>@if($order->shipment)Entrega: <x-order-status :status="$order->shipment->status" />@else Sem registro de expedição @endif</p><a href="{{ route('orders.show', $order->public_number) }}" class="ds-button ds-button--secondary ds-button--md" aria-label="Ver detalhes do pedido {{ $order->public_number }}">Ver detalhes <x-arrow /></a></div>
                        </article>
                    @empty
                        <section class="order-empty ds-panel"><x-icon name="shopping-bag" class="size-12" /><h2>Você ainda não tem pedidos</h2><p>Quando realizar uma compra fictícia, os detalhes e o acompanhamento aparecerão aqui.</p><a href="{{ route('catalog.index') }}" class="ds-button ds-button--primary ds-button--md">Explorar catálogo</a></section>
                    @endforelse
                </div>
                @if($orders->hasPages())<div class="orders-pagination">{{ $orders->links() }}</div>@endif
            </div>
        </div>
    </div></div>
</x-app-layout>