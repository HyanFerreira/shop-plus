<?php

namespace App\Actions\Checkout;

use App\Enums\OrderStatus;
use App\Enums\StockMovementType;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CancelOrder
{
    public function execute(User $user, Order $order): Order
    {
        return DB::transaction(function () use ($user, $order) {
            $order = Order::query()->where('user_id', $user->id)->lockForUpdate()->find($order->id);
            if (! $order) {
                throw ValidationException::withMessages(['order' => 'Pedido não encontrado.']);
            }
            if ($order->status === OrderStatus::Cancelled) {
                return $order;
            }
            if ($order->status !== OrderStatus::PendingPayment) {
                throw ValidationException::withMessages(['order' => 'Este pedido não pode ser cancelado.']);
            }

            $items = $order->items()->orderBy('product_id')->get();
            $inventory = InventoryItem::query()->whereIn('product_id', $items->pluck('product_id'))->orderBy('product_id')->lockForUpdate()->get()->keyBy('product_id');
            foreach ($items as $item) {
                $stock = $inventory->get($item->product_id);
                if (! $stock || $stock->reserved < $item->quantity) {
                    throw ValidationException::withMessages(['order' => 'Não foi possível liberar a reserva.']);
                }
                $key = $this->movementKey($order->id, $item->product_id);
                if (! StockMovement::where('idempotency_key', $key)->exists()) {
                    $stock->decrement('reserved', $item->quantity);
                    StockMovement::create(['inventory_item_id' => $stock->id, 'type' => StockMovementType::ReservationReleased, 'quantity_delta' => -$item->quantity, 'idempotency_key' => $key, 'reference_type' => Order::class, 'reference_id' => $order->id, 'reason' => 'Pedido cancelado pelo cliente', 'actor_id' => $user->id]);
                }
            }

            $order->update(['status' => OrderStatus::Cancelled]);
            $order->statusHistories()->create(['from_status' => OrderStatus::PendingPayment, 'to_status' => OrderStatus::Cancelled, 'actor_id' => $user->id, 'note' => 'Cancelado pelo cliente']);

            return $order->refresh();
        }, 3);
    }

    private function movementKey(int $orderId, int $productId): string
    {
        $hash = md5("cancel:$orderId:$productId");

        return substr($hash, 0, 8).'-'.substr($hash, 8, 4).'-'.substr($hash, 12, 4).'-'.substr($hash, 16, 4).'-'.substr($hash, 20);
    }
}
