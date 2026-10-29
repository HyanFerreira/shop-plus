<ol class="dashboard-progress" aria-label="Etapas do pedido {{ $order->public_number }}">
    @foreach(\App\View\Presenters\DashboardOrderTimeline::stages($order) as $stage)
        <li data-status="{{ $stage['status'] }}" data-step-state="{{ $stage['state'] }}" data-connector-state="{{ $stage['connector'] }}" data-connector-complete="{{ $stage['connector'] === 'complete' ? 'true' : 'false' }}" @if($stage['current']) aria-current="step" @endif>
            <span class="dashboard-progress__marker" aria-hidden="true">@if($stage['state'] === 'recorded')<x-icon name="check" class="dashboard-progress__check" stroke-width="2" />@endif</span>
            <span class="dashboard-progress__label">{{ $stage['label'] }}<span class="sr-only"> — {{ match ($stage['state']) { 'next' => 'Próxima etapa aguardada', 'current' => $stage['context'] === 'Encerrado' ? 'Pedido encerrado' : 'Etapa em andamento', 'interrupted' => 'Etapa interrompida', 'recorded' => 'Etapa concluída', default => 'Sem registro desta etapa' } }}</span></span>
            @if($stage['context'])<span class="dashboard-progress__context sr-only">{{ $stage['context'] }}</span>@endif
        </li>
    @endforeach
</ol>
