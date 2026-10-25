<div class="account-overview">
    <section class="account-stats" aria-label="Resumo dos seus pedidos">
        @foreach ([['title' => 'Pedidos realizados', 'count' => $ordersCount, 'icon' => 'shopping-bag'], ['title' => 'Aguardando pagamento', 'count' => $pendingCount, 'icon' => 'lock-closed'], ['title' => 'Pedidos entregues', 'count' => $deliveredCount, 'icon' => 'truck']] as $stat)
            <div class="account-stat ds-panel"><span class="account-stat__icon"><x-icon :name="$stat['icon']" class="size-6" /></span><div><p>{{ $stat['title'] }}</p><strong>{{ $stat['count'] }}</strong></div></div>
        @endforeach
    </section>
    <section class="account-panel ds-panel" aria-labelledby="recent-orders-title">
        <div class="account-section-heading"><div><h2 id="recent-orders-title">Pedidos recentes</h2><p>Seus últimos pedidos e o andamento de cada compra.</p></div><a href="{{ route('orders.index') }}" class="account-text-link">Ver todos <span aria-hidden="true">→</span></a></div>
        <div class="account-recent-list">
            @forelse ($recentOrders as $order)
                <article class="account-recent-order">
                    <x-order-item-image :item="$order->items->first()" />
                    <div class="account-recent-order__info"><h3>{{ $order->public_number }}</h3><p><time datetime="{{ ($order->placed_at ?? $order->created_at)->toIso8601String() }}">{{ ($order->placed_at ?? $order->created_at)->format('d/m/Y H:i') }}</time> · {{ $order->items->sum('quantity') }} {{ $order->items->sum('quantity') === 1 ? 'unidade' : 'unidades' }}</p><p><span class="sr-only">Status do pedido: </span><x-order-status :status="$order->status" /></p></div>
                    <div class="account-recent-order__action"><strong>{{ \App\Domain\Catalog\Price::fromCents($order->total_cents)->brl() }}</strong><a href="{{ route('orders.show', $order->public_number) }}" class="account-text-link">{{ $order->shipment ? 'Acompanhar pedido' : 'Ver detalhes' }} <span aria-hidden="true">→</span></a></div>
                </article>
            @empty
                <div class="account-empty"><x-icon name="shopping-bag" class="size-10" /><h3>Você ainda não tem pedidos</h3><p>Explore o catálogo. Suas compras fictícias e entregas aparecerão aqui.</p><a href="{{ route('catalog.index') }}" class="ds-button ds-button--primary ds-button--md">Explorar catálogo</a></div>
            @endforelse
        </div>
    </section>
    <section class="account-shortcuts" aria-label="Acessos rápidos">
        <a href="{{ route('personal-data.show') }}" class="account-shortcut ds-panel"><x-icon name="user" class="size-6" /><div><h2>Meus dados</h2><p>Identificação, telefones e endereços.</p></div><span aria-hidden="true">→</span></a>
        <a href="{{ route('profile.show') }}" class="account-shortcut ds-panel"><x-icon name="lock-closed" class="size-6" /><div><h2>Perfil e segurança</h2><p>Perfil, senha e proteção da conta.</p></div><span aria-hidden="true">→</span></a>
        <a href="{{ route('catalog.index') }}" class="account-shortcut ds-panel"><x-icon name="shopping-bag" class="size-6" /><div><h2>Continuar comprando</h2><p>Conheça os produtos do ShopPlus.</p></div><span aria-hidden="true">→</span></a>
    </section>
</div>
