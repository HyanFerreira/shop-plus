<div class="account-overview dashboard-overview">
    <section class="dashboard-hero" aria-labelledby="dashboard-title">
        <div class="dashboard-welcome">
            <div class="account-heading">
                <p class="account-eyebrow">Minha conta</p>
                <h1 id="dashboard-title">Olá, {{ Str::before(Auth::user()->name, ' ') }} <span aria-hidden="true">👋</span></h1>
                <p>Bem-vindo ao ShopPlus. Acompanhe suas compras, gerencie sua conta e encontre seus produtos favoritos.</p>
            </div>
            <ul class="dashboard-benefits" aria-label="Recursos da sua conta">
                @foreach ([['icon' => 'shield-check', 'title' => 'Proteção da conta', 'text' => 'Opções de verificação em duas etapas'], ['icon' => 'truck', 'title' => 'Acompanhe seus pedidos', 'text' => 'Status e entregas em um só lugar'], ['icon' => 'heart-square', 'title' => 'Seus favoritos', 'text' => 'Salve produtos para depois']] as $benefit)
                    <li><span class="dashboard-icon"><x-icon :name="$benefit['icon']" class="size-6 aspect-square shrink-0" /></span><div><strong>{{ $benefit['title'] }}</strong><p>{{ $benefit['text'] }}</p></div></li>
                @endforeach
            </ul>
        </div>
        <div class="dashboard-highlight ds-panel">
            <div class="dashboard-highlight__heading"><span class="dashboard-icon"><x-icon name="user" class="size-6" /></span><p>Seu espaço Shop<span class="ds-shop-logo__accent">Plus</span></p></div>
            <div class="dashboard-highlight__body">
                <div><h2>Sua conta, do seu jeito</h2><p>Mantenha seus dados atualizados e gerencie as opções de proteção da sua conta.</p><a href="{{ route('profile.show') }}#seguranca" class="account-text-link">Cuidar da minha conta <x-arrow /></a></div>
                <div class="dashboard-highlight__art" aria-hidden="true"><x-icon name="shield-check" /></div>
            </div>
        </div>
    </section>

    <section class="dashboard-stats" aria-label="Resumo da sua conta">
        @foreach ([
            ['title' => 'Pedidos realizados', 'count' => $ordersCount, 'icon' => 'shopping-bag', 'tone' => 'brand', 'detail' => 'Seu histórico de compras', 'route' => 'orders.index'],
            ['title' => 'Aguardando pagamento', 'count' => $pendingCount, 'icon' => 'credit-card', 'tone' => 'warning', 'detail' => $pendingCount ? 'Consulte os pedidos pendentes' : 'Nenhum pagamento pendente', 'route' => 'orders.index'],
            ['title' => 'Pedidos entregues', 'count' => $deliveredCount, 'icon' => 'truck', 'tone' => 'success', 'detail' => $deliveredCount ? 'Entregas concluídas' : 'Ainda não há entregas', 'route' => 'orders.index'],
            ['title' => 'Favoritos', 'count' => $favoritesCount, 'icon' => 'heart', 'tone' => 'brand', 'detail' => $favoritesCount ? 'Produtos salvos disponíveis' : 'Nenhum favorito disponível', 'route' => 'favorites.index'],
        ] as $stat)
            <a href="{{ route($stat['route']) }}" class="dashboard-stat ds-panel" data-tone="{{ $stat['tone'] }}">
                <span class="dashboard-icon"><x-icon :name="$stat['icon']" class="size-6" /></span>
                <div class="dashboard-stat__body"><h2>{{ $stat['title'] }}</h2><strong>{{ $stat['count'] }}</strong><p>{{ $stat['detail'] }}</p></div>
                <x-arrow class="dashboard-stat__arrow" />
            </a>
        @endforeach
    </section>

    <section class="account-panel dashboard-orders ds-panel" aria-labelledby="recent-orders-title">
        <div class="account-section-heading">
            <div class="dashboard-orders__heading"><span class="dashboard-icon"><x-icon name="shopping-bag" class="size-5" /></span><div><h2 id="recent-orders-title">Pedidos recentes</h2><p>Seus últimos pedidos e o andamento de cada compra.</p></div></div>
            <a href="{{ route('orders.index') }}" class="account-text-link">Ver todos <x-arrow /></a>
        </div>
        <div class="account-recent-list">
            @forelse ($recentOrders as $order)
                <article class="dashboard-order" wire:key="dashboard-order-{{ $order->id }}" aria-labelledby="dashboard-order-{{ $order->id }}">
                    <div class="dashboard-order__summary">
                        <x-order-item-image :item="$order->items->first()" />
                        <div class="account-recent-order__info">
                            <h3 id="dashboard-order-{{ $order->id }}">{{ $order->public_number }}</h3>
                            <p><time datetime="{{ ($order->placed_at ?? $order->created_at)->toIso8601String() }}">{{ ($order->placed_at ?? $order->created_at)->format('d/m/Y H:i') }}</time> · {{ $order->items->sum('quantity') }} {{ $order->items->sum('quantity') === 1 ? 'unidade' : 'unidades' }}</p>
                            <p><span class="sr-only">Status do pedido: </span><x-order-status :status="$order->status" /></p>
                        </div>
                    </div>
                    <div class="dashboard-order__tracking">
                        @include('livewire.account.partials.dashboard-progress', ['order' => $order])
                        @if($order->shipment && !in_array($order->shipment->status, [\App\Enums\ShipmentStatus::AwaitingProcessing, \App\Enums\ShipmentStatus::Preparing], true))
                            <p class="dashboard-order__shipment"><span>Entrega:</span> <x-order-status :status="$order->shipment->status" /></p>
                        @endif
                    </div>
                    <div class="dashboard-order__action">
                        <strong>{{ \App\Domain\Catalog\Price::fromCents($order->total_cents)->brl() }}</strong>
                        <a href="{{ route('orders.show', $order->public_number) }}" class="ds-button ds-button--secondary ds-button--md" aria-label="{{ $order->shipment ? 'Acompanhar pedido' : 'Ver detalhes' }} {{ $order->public_number }}">{{ $order->shipment ? 'Acompanhar pedido' : 'Ver detalhes' }} <x-arrow /></a>
                    </div>
                </article>
            @empty
                <div class="account-empty"><x-icon name="shopping-bag" class="size-10" /><h3>Você ainda não tem pedidos</h3><p>Explore o catálogo. Suas compras fictícias e entregas aparecerão aqui.</p><a href="{{ route('catalog.index') }}" class="ds-button ds-button--primary ds-button--md">Explorar catálogo</a></div>
            @endforelse
        </div>
    </section>

    <section class="dashboard-shortcuts" aria-label="Acessos rápidos">
        @foreach ([['route' => route('personal-data.show'), 'icon' => 'user', 'title' => 'Dados pessoais e endereços', 'text' => 'Visualize e edite suas informações e endereços de entrega.'], ['route' => route('profile.show').'#seguranca', 'icon' => 'shield-check', 'title' => 'Perfil e segurança', 'text' => 'Gerencie sua senha e a verificação em duas etapas.'], ['route' => route('catalog.index'), 'icon' => 'shopping-cart', 'title' => 'Continuar comprando', 'text' => 'Descubra novos produtos e encontre sua próxima compra.']] as $shortcut)
            <a href="{{ $shortcut['route'] }}" class="dashboard-shortcut ds-panel"><span class="dashboard-icon"><x-icon :name="$shortcut['icon']" class="size-6" /></span><div><h2>{{ $shortcut['title'] }}</h2><p>{{ $shortcut['text'] }}</p></div><x-arrow /></a>
        @endforeach
    </section>
    @if(Auth::user()->isAdmin())
        <a href="{{ route('admin.dashboard') }}" class="account-shortcut ds-panel"><x-icon name="computer-desktop" class="size-6" /><div><h2>Administração</h2><p>Acessar a gestão da loja.</p></div><x-arrow /></a>
    @endif
</div>
