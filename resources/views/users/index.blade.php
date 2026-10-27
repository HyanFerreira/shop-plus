<x-account-shell title="Usuários cadastrados" description="Acompanhe os cadastros do ShopPlus e o crescimento da demonstração acadêmica.">
    <a href="{{ route('monitoring.index') }}" class="account-text-link">Abrir monitoramento de usuários, produtos e transações →</a>
    <div class="account-section-heading"><p>As contagens incluem todas as contas. A lista exibe somente contas fictícias da automação.</p><a href="{{ route('users.index') }}" class="ds-button ds-button--secondary ds-button--md">Atualizar</a></div>

    <section class="account-stats" aria-label="Resumo dos usuários cadastrados">
        @foreach ([['title' => 'Total de usuários', 'count' => $totalUsers, 'icon' => 'users'], ['title' => 'Gerados automaticamente', 'count' => $automatedUsersCount, 'icon' => 'sparkles'], ['title' => 'Novos nos últimos 5 minutos', 'count' => $recentUsersCount, 'icon' => 'user']] as $stat)
            <div class="account-stat ds-panel"><span class="account-stat__icon"><x-icon :name="$stat['icon']" class="size-6" /></span><div><p>{{ $stat['title'] }}</p><strong>{{ $stat['count'] }}</strong></div></div>
        @endforeach
    </section>

    <section class="account-panel ds-panel" aria-labelledby="automated-users-title">
        <div class="account-section-heading"><div><h2 id="automated-users-title">Contas fictícias da automação</h2><p>Cadastros mais recentes primeiro. Horários de Brasília (America/Sao_Paulo). Os dados pessoais dos demais clientes são privados.</p></div></div>
        @if($automatedUsers->isNotEmpty())
            <div class="users-directory__table-scroll" role="region" aria-label="Contas fictícias geradas automaticamente" tabindex="0">
                <table class="users-directory__table">
                    <thead><tr><th scope="col">Identificador</th><th scope="col">Nome fictício</th><th scope="col">Data e horário do cadastro</th><th scope="col">Origem</th></tr></thead>
                    <tbody>
                        @foreach ($automatedUsers as $generatedUser)
                            @php($registeredAt = $generatedUser->created_at->copy()->timezone('America/Sao_Paulo'))
                            <tr><td>#{{ $generatedUser->id }}</td><td>{{ $generatedUser->name }}</td><td><time datetime="{{ $registeredAt->toIso8601String() }}">{{ $registeredAt->format('d/m/Y H:i:s') }}</time></td><td>Automático</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="account-empty"><x-icon name="users" class="size-10" /><h3>Nenhuma conta fictícia gerada ainda.</h3><p>Quando a automação for executada, as novas contas aparecerão aqui.</p></div>
        @endif
    </section>
    @if($automatedUsers->hasPages())<div class="orders-pagination">{{ $automatedUsers->links() }}</div>@endif
</x-account-shell>
