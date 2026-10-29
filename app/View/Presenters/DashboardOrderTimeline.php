<?php

namespace App\View\Presenters;

use App\Enums\OrderStatus;
use App\Enums\ShipmentStatus;
use App\Models\Order;

final class DashboardOrderTimeline
{
    /** Presentation only: this never updates order or shipment state. */
    public static function stages(Order $order): array
    {
        if (in_array($order->status, [OrderStatus::Cancelled, OrderStatus::Refunded], true)) {
            return [[
                'status' => $order->status->value,
                'label' => $order->status === OrderStatus::Cancelled ? 'Cancelado' : 'Reembolsado',
                'state' => 'current', 'context' => 'Encerrado', 'current' => true, 'connector' => 'future',
            ]];
        }

        // The record itself proves creation. Other past stages require history or a current enum.
        $completed = $order->statusHistories
            ->flatMap(fn ($history) => [$history->from_status?->value, $history->to_status->value])
            ->filter()->concat([OrderStatus::PendingPayment->value]);
        if (in_array($order->status, [OrderStatus::Paid, OrderStatus::Shipped, OrderStatus::Delivered], true)) {
            $completed->push($order->status->value);
        }

        $focus = match ($order->status) {
            OrderStatus::PendingPayment => OrderStatus::Paid,
            OrderStatus::Paid, OrderStatus::Processing => OrderStatus::Processing,
            OrderStatus::Shipped => OrderStatus::Shipped,
            default => null,
        };
        $kind = in_array($order->status, [OrderStatus::PendingPayment, OrderStatus::Paid], true) ? 'next' : 'current';
        $context = $kind === 'next' ? 'Aguardando' : ($focus === OrderStatus::Processing ? 'Em andamento' : 'Em transporte');
        $shipmentStatus = $order->shipment?->status;

        // Logistics may establish actual work, but cannot turn an awaited preparation into started work.
        if ($order->status !== OrderStatus::PendingPayment && $order->status !== OrderStatus::Delivered) {
            if ($shipmentStatus === ShipmentStatus::Preparing && in_array($order->status, [OrderStatus::Paid, OrderStatus::Processing], true)) {
                [$focus, $kind, $context] = [OrderStatus::Processing, 'current', 'Em andamento'];
            } elseif ($shipmentStatus === ShipmentStatus::Shipped) {
                [$focus, $kind, $context] = [OrderStatus::Shipped, 'current', 'Em transporte'];
            } elseif ($shipmentStatus === ShipmentStatus::OutForDelivery) {
                $completed->push(OrderStatus::Shipped->value);
                [$focus, $kind, $context] = [OrderStatus::Delivered, 'current', 'A caminho'];
            } elseif ($shipmentStatus === ShipmentStatus::Delivered) {
                $completed->push(OrderStatus::Delivered->value);
                $focus = null;
            }
        }
        if (in_array($shipmentStatus, [ShipmentStatus::Failed, ShipmentStatus::Returned, ShipmentStatus::Cancelled], true)) {
            $focus = null;
            if ($order->status === OrderStatus::Processing) {
                [$focus, $kind, $context] = [OrderStatus::Processing, 'interrupted', 'Interrompido'];
            }
        }
        if ($order->status === OrderStatus::Processing && ! in_array($shipmentStatus, [ShipmentStatus::Shipped, ShipmentStatus::OutForDelivery, ShipmentStatus::Delivered], true)) {
            $completed = $completed->reject(fn ($status) => $status === OrderStatus::Processing->value);
        }

        $stages = [];
        foreach ([
            OrderStatus::PendingPayment->value => 'Pedido criado',
            OrderStatus::Paid->value => 'Pago',
            OrderStatus::Processing->value => 'Preparando',
            OrderStatus::Shipped->value => 'Enviado',
            OrderStatus::Delivered->value => 'Entregue',
        ] as $status => $label) {
            $focused = $status === $focus?->value;
            $stages[] = [
                'status' => $status, 'label' => $label,
                'state' => $focused ? $kind : ($completed->contains($status) ? 'recorded' : 'unrecorded'),
                'context' => $focused ? $context : null,
                'current' => $focused || ($focus === null && $status === $order->status->value),
                'connector' => 'future',
            ];
        }
        foreach ($stages as $index => &$stage) {
            $next = $stages[$index + 1]['state'] ?? null;
            if ($stage['state'] === 'recorded') {
                $stage['connector'] = $next === 'recorded' ? 'complete' : (in_array($next, ['next', 'current', 'interrupted'], true) ? 'focus' : 'future');
            }
        }
        unset($stage);

        return $stages;
    }
}
