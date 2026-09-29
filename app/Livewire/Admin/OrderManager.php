<?php

namespace App\Livewire\Admin;

use App\Actions\Payment\RefundOrder;
use App\Actions\Shipping\TransitionShipment;
use App\Enums\ShipmentStatus;
use App\Models\Order;
use App\Models\Shipment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Component;

class OrderManager extends Component
{
    public string $targetStatus = '';

    public string $note = '';

    public string $refundReason = '';

    public function transition(TransitionShipment $action, int $shipmentId): void
    {
        $validated = $this->validate([
            'targetStatus' => ['required', Rule::enum(ShipmentStatus::class)],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $action->execute(Auth::user(), Shipment::findOrFail($shipmentId), ShipmentStatus::from($validated['targetStatus']), $validated['note']);
        $this->reset('targetStatus', 'note');
        session()->flash('order-admin-status', 'Entrega atualizada.');
    }

    /** @return list<ShipmentStatus> */
    public function options(Shipment $shipment): array
    {
        return match ($shipment->status) {
            ShipmentStatus::AwaitingProcessing => [ShipmentStatus::Preparing, ShipmentStatus::Cancelled],
            ShipmentStatus::Preparing => [ShipmentStatus::Shipped, ShipmentStatus::Cancelled],
            ShipmentStatus::Shipped => [ShipmentStatus::OutForDelivery, ShipmentStatus::Failed, ShipmentStatus::Returned],
            ShipmentStatus::OutForDelivery => [ShipmentStatus::Delivered, ShipmentStatus::Failed],
            ShipmentStatus::Failed => [ShipmentStatus::Preparing, ShipmentStatus::Returned],
            default => [],
        };
    }

    public function refund(RefundOrder $action, int $orderId): void
    {
        $this->validate(['refundReason' => ['required', 'string', 'min:5', 'max:500']]);
        $action->execute(Auth::user(), Order::findOrFail($orderId), $this->refundReason);
        $this->reset('refundReason');
        session()->flash('order-admin-status', 'Pedido reembolsado e estoque devolvido.');
    }

    public function render()
    {
        abort_unless(Auth::user()?->isAdmin(), 403);

        return view('livewire.admin.order-manager', [
            'orders' => Order::query()->with(['user', 'payments', 'shipment.events'])->latest()->paginate(15),
        ]);
    }
}
