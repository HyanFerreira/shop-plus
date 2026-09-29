<?php

namespace App\Actions\Inventory;

use App\Enums\PurchaseOrderStatus;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SavePurchaseOrder
{
    /** @param array<int, int> $lines Product ID => quantity. */
    public function execute(User $actor, ?int $orderId, int $supplierId, array $lines): PurchaseOrder
    {
        abort_unless($actor->isAdmin(), 403);

        return DB::transaction(function () use ($orderId, $supplierId, $lines) {
            $supplier = Supplier::query()->where('active', true)->findOrFail($supplierId);
            $order = $orderId ? PurchaseOrder::query()->lockForUpdate()->findOrFail($orderId) : new PurchaseOrder;

            if ($order->exists && $order->status !== PurchaseOrderStatus::Draft) {
                throw ValidationException::withMessages(['order' => 'Somente ordens em rascunho podem ser alteradas.']);
            }
            if ($lines === []) {
                throw ValidationException::withMessages(['orderLines' => 'Inclua ao menos um produto.']);
            }

            $links = $supplier->supplierProducts()->whereIn('product_id', array_keys($lines))->get()->keyBy('product_id');
            if ($links->count() !== count($lines) || collect($lines)->contains(fn ($qty) => ! is_int($qty) || $qty < 1 || $qty > 100000)) {
                throw ValidationException::withMessages(['orderLines' => 'Produtos ou quantidades inválidos.']);
            }

            $total = $links->sum(fn ($link) => $link->cost_cents * $lines[$link->product_id]);
            $order->fill([
                'supplier_id' => $supplier->id,
                'public_number' => $order->public_number ?: 'PO-'.Str::upper(Str::random(16)),
                'status' => PurchaseOrderStatus::Draft,
                'total_cents' => $total,
            ])->save();
            $order->items()->delete();
            foreach ($links as $link) {
                $order->items()->create([
                    'product_id' => $link->product_id,
                    'quantity_ordered' => $lines[$link->product_id],
                    'quantity_received' => 0,
                    'unit_cost_cents' => $link->cost_cents,
                ]);
            }

            return $order->load('items');
        });
    }
}
