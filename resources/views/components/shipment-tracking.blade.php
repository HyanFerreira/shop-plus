@props(['shipment'])
<section class="order-panel ds-panel" aria-labelledby="tracking-title">
    <div class="order-section-heading"><div><p class="order-eyebrow">Andamento logístico</p><h2 id="tracking-title">Acompanhamento da entrega</h2></div>@if($shipment)<x-order-status :status="$shipment->status" />@endif</div>
    @if (! $shipment)
        <div class="order-information"><x-icon name="truck" class="size-8" /><div><h3>Sem registro de expedição</h3><p>Este pedido ainda não possui uma entrega associada. Não há código ou movimentações de rastreamento disponíveis.</p></div></div>
    @else
        @php
            $stages = [
                ['status' => \App\Enums\ShipmentStatus::AwaitingProcessing, 'label' => 'Aguardando preparação'],
                ['status' => \App\Enums\ShipmentStatus::Preparing, 'label' => 'Em preparação'],
                ['status' => \App\Enums\ShipmentStatus::Shipped, 'label' => 'Em transporte'],
                ['status' => \App\Enums\ShipmentStatus::OutForDelivery, 'label' => 'Saiu para entrega'],
                ['status' => \App\Enums\ShipmentStatus::Delivered, 'label' => 'Entrega concluída'],
            ];
            $events = $shipment->events->sort(fn ($a, $b) => ($a->created_at <=> $b->created_at) ?: ($a->id <=> $b->id));
            $evidence = $events->flatMap(fn ($event) => [$event->from_status, $event->to_status])->filter();
            $currentIndex = collect($stages)->search(fn ($stage) => $stage['status'] === $shipment->status);
            $exception = match ($shipment->status) {
                \App\Enums\ShipmentStatus::Failed => 'Uma falha foi registrada na entrega. Consulte as movimentações abaixo; não há uma nova data confirmada.',
                \App\Enums\ShipmentStatus::Returned => 'A entrega foi devolvida. Isso não confirma, por si só, o reembolso do pedido.',
                \App\Enums\ShipmentStatus::Cancelled => 'A entrega foi cancelada. O status do pedido e o pagamento são apresentados separadamente.',
                default => null,
            };
        @endphp
        @if($exception)<x-alert :variant="$shipment->status === \App\Enums\ShipmentStatus::Failed ? 'danger' : 'warning'" class="order-tracking-alert">{{ $exception }}</x-alert>@endif
        <ol class="shipment-progress" aria-label="Etapas logísticas registradas">
            @foreach ($stages as $index => $stage)
                @php
                    $seen = $evidence->contains($stage['status']);
                    $state = $currentIndex === $index ? 'current' : ($seen ? ($currentIndex !== false && $index < $currentIndex ? 'complete' : 'recorded') : ($currentIndex !== false && $index < $currentIndex ? 'unknown' : 'future'));
                    $stateLabel = match ($state) { 'current' => 'Etapa atual', 'complete' => 'Concluída · registrada', 'recorded' => 'Registro anterior', 'unknown' => 'Sem registro', default => 'Ainda não realizada' };
                @endphp
                <li data-step-state="{{ $state }}" @if($state === 'current') aria-current="step" @endif>
                    <span class="shipment-progress__marker" aria-hidden="true">{{ $state === 'complete' ? '✓' : ($index + 1) }}</span>
                    <div><p>{{ $stage['label'] }}</p><span>{{ $stateLabel }}</span></div>
                </li>
            @endforeach
        </ol>
        <div class="order-tracking-context">
            <div><h3>Rastreamento fictício</h3>@if($shipment->tracking_code)<p class="order-tracking-code">{{ $shipment->tracking_code }}</p>@else<p>Código não informado.</p>@endif<p>Simulação acadêmica, sem consulta a transportadoras externas.</p></div>
            <div><h3>Datas registradas</h3>@if($shipment->shipped_at)<p>Envio: <time datetime="{{ $shipment->shipped_at->toIso8601String() }}">{{ $shipment->shipped_at->format('d/m/Y H:i') }}</time></p>@endif @if($shipment->delivered_at)<p>Entrega registrada: <time datetime="{{ $shipment->delivered_at->toIso8601String() }}">{{ $shipment->delivered_at->format('d/m/Y H:i') }}</time></p>@else<p>Data de entrega não informada.</p>@endif</div>
        </div>
        <h3 class="order-history-heading">Histórico de movimentações</h3>
        <ol class="order-event-list">
            @forelse($events as $event)
                <li><time datetime="{{ $event->created_at->toIso8601String() }}">{{ $event->created_at->format('d/m/Y H:i') }}</time><div><x-order-status :status="$event->to_status" />@if($event->note)<p>{{ $event->note }}</p>@endif</div></li>
            @empty
                <li><p>Nenhuma movimentação registrada. O estado atual da entrega é o informado acima.</p></li>
            @endforelse
        </ol>
    @endif
</section>
