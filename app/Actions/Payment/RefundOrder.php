<?php

namespace App\Actions\Payment;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ShipmentStatus;
use App\Enums\StockMovementType;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\Payment;
use App\Models\StockMovement;
use App\Models\User;
use App\Support\SecurityAudit;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RefundOrder
{
    public function execute(User $actor, Order $order, string $reason): Order
    {
        if (! $actor->isAdmin()) {
            throw new AuthorizationException;
        }
        if (mb_strlen(trim($reason)) < 5 || mb_strlen($reason) > 500) {
            throw ValidationException::withMessages(['refund' => 'Informe uma justificativa válida.']);
        }

        return DB::transaction(function () use ($actor, $order, $reason) {
            $order = Order::query()->with('shipment')->lockForUpdate()->findOrFail($order->id);
            if ($order->status === OrderStatus::Refunded) {
                return $order;
            }
            if (! in_array($order->status, [OrderStatus::Paid, OrderStatus::Processing, OrderStatus::Shipped, OrderStatus::Delivered], true)) {
                throw ValidationException::withMessages(['refund' => 'Este pedido não pode ser reembolsado.']);
            }
            $payment = Payment::query()->where('order_id', $order->id)->where('status', PaymentStatus::Authorized)->lockForUpdate()->latest('id')->first();
            if (! $payment) {
                throw ValidationException::withMessages(['refund' => 'Pagamento autorizado não encontrado.']);
            }

            $items = $order->items()->orderBy('product_id')->get();
            $inventory = InventoryItem::query()->whereIn('product_id', $items->pluck('product_id'))->orderBy('product_id')->lockForUpdate()->get()->keyBy('product_id');
            foreach ($items as $item) {
                $stock = $inventory->get($item->product_id) ?? InventoryItem::create(['product_id' => $item->product_id]);
                $key = $this->movementKey($order->id, $item->product_id);
                if (! StockMovement::where('idempotency_key', $key)->exists()) {
                    $stock->increment('on_hand', $item->quantity);
                    StockMovement::create(['inventory_item_id' => $stock->id, 'type' => StockMovementType::Returned, 'quantity_delta' => $item->quantity, 'idempotency_key' => $key, 'reference_type' => Payment::class, 'reference_id' => $payment->id, 'reason' => trim($reason), 'actor_id' => $actor->id]);
                }
            }

            $previous = $order->status;
            $payment->update(['status' => PaymentStatus::Refunded]);
            $order->update(['status' => OrderStatus::Refunded, 'refunded_at' => now()]);
            $order->statusHistories()->create(['from_status' => $previous, 'to_status' => OrderStatus::Refunded, 'actor_id' => $actor->id, 'note' => trim($reason)]);
            if ($order->shipment && ! in_array($order->shipment->status, [ShipmentStatus::Returned, ShipmentStatus::Cancelled], true)) {
                $shipmentStatus = in_array($order->shipment->status, [ShipmentStatus::AwaitingProcessing, ShipmentStatus::Preparing], true) ? ShipmentStatus::Cancelled : ShipmentStatus::Returned;
                $from = $order->shipment->status;
                $order->shipment->update(['status' => $shipmentStatus]);
                $order->shipment->events()->create(['from_status' => $from, 'to_status' => $shipmentStatus, 'note' => 'Encerrada por reembolso', 'actor_id' => $actor->id]);
            }
            app(SecurityAudit::class)->record($actor, 'order.refunded', $order, ['payment_id' => $payment->id]);

            return $order->load('payments', 'shipment.events');
        }, 3);
    }

    private function movementKey(int $orderId, int $productId): string
    {
        $hash = md5("refund:$orderId:$productId");

        return substr($hash, 0, 8).'-'.substr($hash, 8, 4).'-'.substr($hash, 12, 4).'-'.substr($hash, 16, 4).'-'.substr($hash, 20);
    }
}
