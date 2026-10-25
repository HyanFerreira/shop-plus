@props(['status'])
@php
    // Presentation only. The enum type keeps payment, order and logistics distinct.
    [$label, $variant] = match ($status) {
        \App\Enums\OrderStatus::PendingPayment => ['Pagamento pendente', 'warning'],
        \App\Enums\OrderStatus::Paid => ['Pago', 'success'],
        \App\Enums\OrderStatus::Processing => ['Em processamento', 'info'],
        \App\Enums\OrderStatus::Shipped => ['Enviado', 'info'],
        \App\Enums\OrderStatus::Delivered => ['Entregue', 'success'],
        \App\Enums\OrderStatus::Cancelled => ['Cancelado', 'neutral'],
        \App\Enums\OrderStatus::Refunded => ['Reembolsado', 'neutral'],
        \App\Enums\ShipmentStatus::AwaitingProcessing => ['Aguardando preparação', 'neutral'],
        \App\Enums\ShipmentStatus::Preparing => ['Em preparação', 'info'],
        \App\Enums\ShipmentStatus::Shipped => ['Em transporte', 'info'],
        \App\Enums\ShipmentStatus::OutForDelivery => ['Saiu para entrega', 'info'],
        \App\Enums\ShipmentStatus::Delivered => ['Entrega concluída', 'success'],
        \App\Enums\ShipmentStatus::Failed => ['Falha na entrega', 'danger'],
        \App\Enums\ShipmentStatus::Returned => ['Devolvida', 'warning'],
        \App\Enums\ShipmentStatus::Cancelled => ['Entrega cancelada', 'neutral'],
        \App\Enums\PaymentStatus::Pending => ['Pagamento pendente', 'warning'],
        \App\Enums\PaymentStatus::Authorized => ['Pagamento autorizado', 'success'],
        \App\Enums\PaymentStatus::Failed => ['Pagamento recusado', 'danger'],
        \App\Enums\PaymentStatus::Cancelled => ['Pagamento cancelado', 'neutral'],
        \App\Enums\PaymentStatus::Refunded => ['Pagamento reembolsado', 'neutral'],
    };
@endphp
<x-badge :variant="$variant" {{ $attributes }}>{{ $label }}</x-badge>
