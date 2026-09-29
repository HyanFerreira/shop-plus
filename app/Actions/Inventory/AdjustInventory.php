<?php

namespace App\Actions\Inventory;

use App\Enums\StockMovementType;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Support\SecurityAudit;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdjustInventory
{
    public function execute(User $actor, Product $product, int $delta, string $reason, string $idempotencyKey): InventoryItem
    {
        abort_unless($actor->isAdmin(), 403);
        if ($delta === 0 || mb_strlen(trim($reason)) < 5) {
            throw ValidationException::withMessages(['adjustment' => 'Informe quantidade e justificativa válidas.']);
        }

        return DB::transaction(function () use ($actor, $product, $delta, $reason, $idempotencyKey) {
            if (StockMovement::where('idempotency_key', $idempotencyKey)->exists()) {
                return InventoryItem::where('product_id', $product->id)->firstOrFail();
            }

            InventoryItem::firstOrCreate(['product_id' => $product->id]);
            $inventory = InventoryItem::where('product_id', $product->id)->lockForUpdate()->firstOrFail();
            $newOnHand = $inventory->on_hand + $delta;
            if ($newOnHand < 0 || $newOnHand < $inventory->reserved) {
                throw ValidationException::withMessages(['adjustment' => 'O ajuste deixaria o estoque abaixo da quantidade reservada.']);
            }

            $inventory->update(['on_hand' => $newOnHand]);
            $inventory->movements()->create([
                'type' => StockMovementType::Adjustment,
                'quantity_delta' => $delta,
                'idempotency_key' => $idempotencyKey,
                'reference_type' => Product::class,
                'reference_id' => $product->id,
                'reason' => trim($reason),
                'actor_id' => $actor->id,
            ]);
            app(SecurityAudit::class)->record($actor, 'inventory.adjusted', $inventory, ['delta' => $delta, 'product_id' => $product->id]);

            return $inventory->refresh();
        }, 3);
    }
}
