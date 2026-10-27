@if($orders->isNotEmpty())
    <div class="users-directory__table-scroll" role="region" aria-label="Transações fictícias" tabindex="0">
        <table class="users-directory__table monitoring-transactions-table">
            <thead><tr><th scope="col">Pedido</th><th scope="col">Cliente fictício</th><th scope="col">Produtos e quantidades</th><th scope="col">Total</th><th scope="col">Cadastro (Brasília)</th><th scope="col">Estado atual</th></tr></thead>
            <tbody>
                @foreach($orders as $transaction)
                    <tr>
                        <td><a class="account-text-link monitoring-order-number" href="{{ route('monitoring.orders.show', $transaction->id) }}">{{ $transaction->public_number }}</a></td>
                        <td><a class="account-text-link" href="{{ route('monitoring.users.show', $transaction->user_id) }}">{{ $transaction->user->name }} #{{ $transaction->user_id }}</a></td>
                        <td><ul>@foreach($transaction->items as $item)<li><a class="account-text-link" href="{{ route('monitoring.products.show', $item->product_id) }}">{{ $item->product->name }}</a> × {{ $item->quantity }}</li>@endforeach</ul></td>
                        <td>{{ \App\Domain\Catalog\Price::fromCents($transaction->total_cents)->brl() }}</td>
                        <td>@include('monitoring.partials.time', ['date' => $transaction->created_at])</td>
                        <td><x-order-status :status="$transaction->status" /></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @if($orders->hasPages())<div class="orders-pagination">{{ $orders->links() }}</div>@endif
@else
    <div class="account-empty"><x-icon name="shopping-bag" class="size-10" /><h3>Nenhuma transação fictícia encontrada.</h3><p>As compras aparecerão aqui quando houver condições válidas para a simulação.</p></div>
@endif
