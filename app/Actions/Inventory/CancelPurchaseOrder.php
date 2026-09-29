<?php

namespace App\Actions\Inventory;

use App\Enums\PurchaseOrderStatus;
use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CancelPurchaseOrder
{
    public function execute(User $actor, PurchaseOrder $order): PurchaseOrder
    {
        abort_unless($actor->isAdmin(), 403);

        return DB::transaction(function () use ($order) {
            $order = PurchaseOrder::query()->lockForUpdate()->findOrFail($order->id);
            if (! in_array($order->status, [PurchaseOrderStatus::Draft, PurchaseOrderStatus::Placed], true)) {
                throw ValidationException::withMessages(['order' => 'A ordem não pode ser cancelada.']);
            }
            $order->update(['status' => PurchaseOrderStatus::Cancelled, 'cancelled_at' => now()]);

            return $order->refresh();
        });
    }
}
