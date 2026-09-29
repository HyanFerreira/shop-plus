<?php

namespace App\Actions\Checkout;

use App\Enums\OrderStatus;
use App\Enums\StockMovementType;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\StockMovement;
use App\Support\SecurityAudit;
use Illuminate\Support\Facades\DB;

class ExpireOrder
{
    public function execute(Order $order): Order
    {
        return DB::transaction(function () use ($order) {
            $order = Order::query()->lockForUpdate()->findOrFail($order->id);
            if ($order->status !== OrderStatus::PendingPayment || ! $order->payment_expires_at?->isPast()) {
                return $order;
            }

            $items = $order->items()->orderBy('product_id')->get();
            $inventory = InventoryItem::query()->whereIn('product_id', $items->pluck('product_id'))->orderBy('product_id')->lockForUpdate()->get()->keyBy('product_id');
            foreach ($items as $item) {
                $stock = $inventory->get($item->product_id);
                $key = $this->movementKey($order->id, $item->product_id);
                if ($stock && ! StockMovement::where('idempotency_key', $key)->exists()) {
                    $stock->decrement('reserved', $item->quantity);
                    StockMovement::create(['inventory_item_id' => $stock->id, 'type' => StockMovementType::ReservationReleased, 'quantity_delta' => -$item->quantity, 'idempotency_key' => $key, 'reference_type' => Order::class, 'reference_id' => $order->id, 'reason' => 'Reserva expirada automaticamente']);
                }
            }

            $order->update(['status' => OrderStatus::Cancelled, 'cancelled_at' => now()]);
            $order->statusHistories()->create(['from_status' => OrderStatus::PendingPayment, 'to_status' => OrderStatus::Cancelled, 'note' => 'Prazo de pagamento expirado']);
            app(SecurityAudit::class)->record(null, 'order.expired', $order);

            return $order->refresh();
        }, 3);
    }

    private function movementKey(int $orderId, int $productId): string
    {
        $hash = md5("expire:$orderId:$productId");

        return substr($hash, 0, 8).'-'.substr($hash, 8, 4).'-'.substr($hash, 12, 4).'-'.substr($hash, 16, 4).'-'.substr($hash, 20);
    }
}
