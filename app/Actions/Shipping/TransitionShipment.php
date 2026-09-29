<?php

namespace App\Actions\Shipping;

use App\Enums\OrderStatus;
use App\Enums\ShipmentStatus;
use App\Models\Shipment;
use App\Models\User;
use App\Support\SecurityAudit;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TransitionShipment
{
    /** @var array<string, list<ShipmentStatus>> */
    private const TRANSITIONS = [
        'awaiting_processing' => [ShipmentStatus::Preparing, ShipmentStatus::Cancelled],
        'preparing' => [ShipmentStatus::Shipped, ShipmentStatus::Cancelled],
        'shipped' => [ShipmentStatus::OutForDelivery, ShipmentStatus::Failed, ShipmentStatus::Returned],
        'out_for_delivery' => [ShipmentStatus::Delivered, ShipmentStatus::Failed],
        'failed' => [ShipmentStatus::Preparing, ShipmentStatus::Returned],
    ];

    public function execute(User $actor, Shipment $shipment, ShipmentStatus $target, ?string $note): Shipment
    {
        if (! $actor->isAdmin()) {
            throw new AuthorizationException;
        }
        if ($note !== null && mb_strlen($note) > 500) {
            throw ValidationException::withMessages(['note' => 'A observação deve ter no máximo 500 caracteres.']);
        }

        return DB::transaction(function () use ($actor, $shipment, $target, $note) {
            $shipment = Shipment::query()->with('order')->lockForUpdate()->findOrFail($shipment->id);
            $allowed = self::TRANSITIONS[$shipment->status->value] ?? [];
            if (! in_array($target, $allowed, true)) {
                throw ValidationException::withMessages(['shipment' => 'Transição de entrega inválida.']);
            }

            $from = $shipment->status;
            $attributes = ['status' => $target];
            if ($target === ShipmentStatus::Shipped) {
                $attributes['shipped_at'] = now();
            }
            if ($target === ShipmentStatus::Delivered) {
                $attributes['delivered_at'] = now();
            }
            $shipment->update($attributes);
            $shipment->events()->create(['from_status' => $from, 'to_status' => $target, 'note' => $note ? trim($note) : null, 'actor_id' => $actor->id]);

            $orderStatus = match ($target) {
                ShipmentStatus::Preparing => OrderStatus::Processing,
                ShipmentStatus::Shipped, ShipmentStatus::OutForDelivery => OrderStatus::Shipped,
                ShipmentStatus::Delivered => OrderStatus::Delivered,
                default => null,
            };
            if ($orderStatus && $shipment->order->status !== $orderStatus) {
                $previous = $shipment->order->status;
                $shipment->order->update(['status' => $orderStatus]);
                $shipment->order->statusHistories()->create(['from_status' => $previous, 'to_status' => $orderStatus, 'actor_id' => $actor->id, 'note' => 'Atualização da entrega simulada']);
            }
            app(SecurityAudit::class)->record($actor, 'shipment.transitioned', $shipment, ['from' => $from->value, 'to' => $target->value]);

            return $shipment->load('events', 'order');
        }, 3);
    }
}
