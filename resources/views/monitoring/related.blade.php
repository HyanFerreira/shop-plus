<x-account-shell :title="$subject->name" description="Pedidos fictícios associados a este registro da automação.">
    <a class="account-text-link" href="{{ route('monitoring.index', ['tab' => $kind === 'user' ? 'users' : 'products']) }}">← Voltar ao monitoramento</a>
    <section class="account-panel ds-panel" aria-labelledby="related-orders-title">
        <div class="account-section-heading"><div><h2 id="related-orders-title">Pedidos simulados — #{{ $subject->id }}</h2><p>Somente compras associadas a contas e produtos da automação. Horários de Brasília.</p></div></div>
        @include('monitoring.partials.orders')
    </section>
</x-account-shell>
