<?php

namespace App\Actions\Inventory;

use App\Enums\PurchaseOrderStatus;
use App\Enums\StockMovementType;
use App\Models\InventoryItem;
use App\Models\PurchaseOrder;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReceivePurchase
{
    /** @param array<int, int> $quantities Item ID => quantity received now. */
    public function execute(PurchaseOrder $order, array $quantities, string $idempotencyKey): PurchaseOrder
    {
        return DB::transaction(function () use ($order, $quantities, $idempotencyKey) {
            $order = PurchaseOrder::query()->lockForUpdate()->findOrFail($order->id);
            $items = $order->items()->lockForUpdate()->get()->keyBy('id');

            $keys = collect(array_keys($quantities))->mapWithKeys(fn ($itemId) => [
                $itemId => $this->movementKey($idempotencyKey, (int) $itemId),
            ]);

            if ($keys->isNotEmpty() && StockMovement::whereIn('idempotency_key', $keys)->count() === $keys->count()) {
                return $order;
            }

            if (! in_array($order->status, [PurchaseOrderStatus::Placed, PurchaseOrderStatus::PartiallyReceived], true)) {
                throw ValidationException::withMessages(['order' => 'Esta ordem não pode receber itens.']);
            }

            foreach ($quantities as $itemId => $quantity) {
                $item = $items->get((int) $itemId);

                if (! $item || $quantity < 1 || $item->quantity_received + $quantity > $item->quantity_ordered) {
                    throw ValidationException::withMessages(['receipt' => 'Quantidade de recebimento inválida.']);
                }

                $movementKey = $keys->get((int) $itemId);
                if (StockMovement::where('idempotency_key', $movementKey)->exists()) {
                    continue;
                }

                $inventory = InventoryItem::query()->where('product_id', $item->product_id)->lockForUpdate()->first();
                $inventory ??= InventoryItem::create(['product_id' => $item->product_id]);
                $inventory->increment('on_hand', $quantity);

                $inventory->movements()->create([
                    'type' => StockMovementType::PurchaseReceived,
                    'quantity_delta' => $quantity,
                    'idempotency_key' => $movementKey,
                    'reference_type' => $item::class,
                    'reference_id' => $item->id,
                    'reason' => 'Recebimento de ordem de compra',
                ]);

                $item->increment('quantity_received', $quantity);
            }

            $order->status = $order->items()->whereColumn('quantity_received', '<', 'quantity_ordered')->exists()
                ? PurchaseOrderStatus::PartiallyReceived
                : PurchaseOrderStatus::Received;
            $order->received_at = $order->status === PurchaseOrderStatus::Received ? now() : null;
            $order->save();

            return $order->refresh();
        }, 3);
    }

    private function movementKey(string $key, int $itemId): string
    {
        $hash = md5($key.':'.$itemId);

        return substr($hash, 0, 8).'-'.substr($hash, 8, 4).'-'.substr($hash, 12, 4).'-'.substr($hash, 16, 4).'-'.substr($hash, 20);
    }
}
