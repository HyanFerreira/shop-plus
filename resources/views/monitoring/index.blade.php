<x-account-shell title="Monitoramento acadêmico" description="Relações reais entre contas, produtos e compras fictícias da automação. Nenhuma cobrança real.">
    <div class="account-section-heading"><p>Somente dados sintéticos são exibidos. Todas as datas e filtros usam America/Sao_Paulo.</p><a href="{{ route('monitoring.index', request()->only(['tab', 'client_id', 'status', 'from', 'to', 'page'])) }}" class="ds-button ds-button--secondary ds-button--md">Atualizar</a></div>
    <section class="account-stats" aria-label="Resumo dos usuários cadastrados">
        @foreach ([['title' => 'Total de usuários', 'count' => $totalUsers, 'icon' => 'users'], ['title' => 'Gerados automaticamente', 'count' => $automatedUsersCount, 'icon' => 'sparkles'], ['title' => 'Novos nos últimos 5 minutos', 'count' => $recentUsersCount, 'icon' => 'user']] as $stat)
            <div class="account-stat ds-panel"><span class="account-stat__icon"><x-icon :name="$stat['icon']" class="size-6" /></span><div><p>{{ $stat['title'] }}</p><strong>{{ $stat['count'] }}</strong></div></div>
        @endforeach
    </section>
    <nav class="account-profile-nav" aria-label="Seções do monitoramento">
        @foreach(['users' => 'Usuários', 'products' => 'Produtos', 'transactions' => 'Transações'] as $key => $label)
            <a class="ds-button {{ $tab === $key ? 'ds-button--primary' : 'ds-button--secondary' }} ds-button--md" href="{{ route('monitoring.index', ['tab' => $key]) }}" @if($tab === $key) aria-current="page" @endif>{{ $label }}</a>
        @endforeach
    </nav>
    <section class="account-panel ds-panel" aria-labelledby="monitoring-list-title">
        <div class="account-section-heading"><div><h2 id="monitoring-list-title">{{ ['users' => 'Contas fictícias', 'products' => 'Produtos fictícios', 'transactions' => 'Transações fictícias'][$tab] }}</h2><p>Selecione um registro para acompanhar suas relações. Valores pagos incluem pedidos pagos, em processamento, enviados e entregues.</p></div></div>
        @if($tab === 'transactions')
            <form action="{{ route('monitoring.index') }}" method="GET" class="monitoring-filters">
                <input type="hidden" name="tab" value="transactions">
                <div><label for="monitoring-client">Identificador do cliente fictício</label><input id="monitoring-client" class="ds-input" type="number" min="1" name="client_id" value="{{ $filters['client_id'] ?? '' }}"></div>
                <div><label for="monitoring-status">Estado do pedido</label><select id="monitoring-status" class="ds-input" name="status"><option value="">Todos</option>@foreach(['pending_payment' => 'Pagamento pendente', 'paid' => 'Pago', 'processing' => 'Em processamento', 'shipped' => 'Enviado', 'delivered' => 'Entregue', 'cancelled' => 'Cancelado', 'refunded' => 'Reembolsado'] as $value => $label)<option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>@endforeach</select></div>
                <div><label for="monitoring-from">Desde (Brasília)</label><input id="monitoring-from" class="ds-input" type="date" name="from" value="{{ $filters['from'] ?? '' }}"></div>
                <div><label for="monitoring-to">Até (Brasília)</label><input id="monitoring-to" class="ds-input" type="date" name="to" value="{{ $filters['to'] ?? '' }}"></div>
                <button class="ds-button ds-button--secondary ds-button--md" type="submit">Filtrar</button><a class="account-text-link" href="{{ route('monitoring.index', ['tab' => 'transactions']) }}">Limpar filtros</a>
                @if($errors->any())<p role="alert">Confira o identificador, o estado e o intervalo de datas informado.</p>@endif
            </form>
            @include('monitoring.partials.orders', ['orders' => $records])
        @elseif($records->isEmpty())
            <div class="account-empty"><x-icon name="{{ $tab === 'users' ? 'users' : 'cube' }}" class="size-10" /><h3>Nenhum registro fictício encontrado.</h3><p>Os novos registros aparecerão após a execução da automação.</p></div>
        @else
            <div class="users-directory__table-scroll" role="region" aria-label="{{ $tab === 'users' ? 'Contas fictícias' : 'Produtos fictícios' }}" tabindex="0">
                <table class="users-directory__table">
                    @if($tab === 'users')
                        <thead><tr><th scope="col">Identificador e nome fictício</th><th scope="col">Cadastro (Brasília)</th><th scope="col">Pedidos simulados</th><th scope="col">Valor de compras pagas</th></tr></thead>
                        <tbody>@foreach($records as $user)<tr><td><a class="account-text-link" href="{{ route('monitoring.users.show', $user->id) }}">#{{ $user->id }} {{ $user->name }}</a></td><td>@include('monitoring.partials.time', ['date' => $user->created_at])</td><td>{{ $user->simulated_orders_count }}</td><td>{{ \App\Domain\Catalog\Price::fromCents((int) $user->paid_purchases_cents)->brl() }}</td></tr>@endforeach</tbody>
                    @else
                        <thead><tr><th scope="col">Produto fictício</th><th scope="col">SKU</th><th scope="col">Estoque disponível / físico / reservado</th><th scope="col">Preço</th><th scope="col">Unidades vendidas</th><th scope="col">Pedidos simulados</th></tr></thead>
                        <tbody>@foreach($records as $product)<tr><td><a class="account-text-link" href="{{ route('monitoring.products.show', $product->id) }}">@if($product->images->first())<img class="monitoring-product-image" src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($product->images->first()->path) }}" alt="" width="64" height="64" loading="lazy">@endif #{{ $product->id }} {{ $product->name }}</a></td><td>{{ $product->sku }}</td><td>{{ $product->inventoryItem?->available() ?? 0 }} / {{ $product->inventoryItem?->on_hand ?? 0 }} / {{ $product->inventoryItem?->reserved ?? 0 }}</td><td>{{ \App\Domain\Catalog\Price::fromCents($product->price_cents)->brl() }}</td><td>{{ $product->sold_quantity }}</td><td>{{ $product->simulated_orders_count }}</td></tr>@endforeach</tbody>
                    @endif
                </table>
            </div>
            @if($records->hasPages())<div class="orders-pagination">{{ $records->links() }}</div>@endif
        @endif
    </section>
</x-account-shell>
