<?php

namespace App\Actions\Inventory;

use App\Enums\PurchaseOrderStatus;
use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PlacePurchaseOrder
{
    public function execute(User $actor, PurchaseOrder $order): PurchaseOrder
    {
        abort_unless($actor->isAdmin(), 403);

        return DB::transaction(function () use ($order) {
            $order = PurchaseOrder::query()->lockForUpdate()->findOrFail($order->id);
            if ($order->status !== PurchaseOrderStatus::Draft || ! $order->items()->exists()) {
                throw ValidationException::withMessages(['order' => 'A ordem não pode ser emitida.']);
            }
            $order->update(['status' => PurchaseOrderStatus::Placed, 'placed_at' => now()]);

            return $order->refresh();
        });
    }
}
