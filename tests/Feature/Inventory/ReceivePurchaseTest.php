<?php

namespace Tests\Feature\Inventory;

use App\Actions\Inventory\ReceivePurchase;
use App\Enums\PurchaseOrderStatus;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ReceivePurchaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_partial_and_full_receipts_update_stock_and_order_state(): void
    {
        $order = PurchaseOrder::factory()->create(['status' => PurchaseOrderStatus::Placed]);
        $product = Product::factory()->create();
        $item = PurchaseOrderItem::factory()->for($order)->for($product)->create(['quantity_ordered' => 5]);
        $action = app(ReceivePurchase::class);
        $admin = User::factory()->admin()->create();

        $action->execute($admin, $order, [$item->id => 2], 'receipt-00000001');

        $this->assertSame(PurchaseOrderStatus::PartiallyReceived, $order->fresh()->status);
        $this->assertSame(2, $item->fresh()->quantity_received);
        $this->assertSame(2, $product->inventoryItem->on_hand);

        $action->execute($admin, $order->fresh(), [$item->id => 3], 'receipt-00000002');

        $this->assertSame(PurchaseOrderStatus::Received, $order->fresh()->status);
        $this->assertSame(5, $product->inventoryItem->fresh()->on_hand);
        $this->assertDatabaseCount('stock_movements', 2);
    }

    public function test_repeated_idempotency_key_does_not_duplicate_stock(): void
    {
        $order = PurchaseOrder::factory()->create(['status' => PurchaseOrderStatus::Placed]);
        $item = PurchaseOrderItem::factory()->for($order)->create(['quantity_ordered' => 4]);
        $action = app(ReceivePurchase::class);
        $admin = User::factory()->admin()->create();

        $action->execute($admin, $order, [$item->id => 2], 'receipt-00000003');
        $action->execute($admin, $order->fresh(), [$item->id => 2], 'receipt-00000003');

        $this->assertSame(2, $item->fresh()->quantity_received);
        $this->assertSame(2, $item->product->inventoryItem->on_hand);
        $this->assertDatabaseCount('stock_movements', 1);
    }

    public function test_receipt_cannot_exceed_ordered_quantity(): void
    {
        $order = PurchaseOrder::factory()->create(['status' => PurchaseOrderStatus::Placed]);
        $item = PurchaseOrderItem::factory()->for($order)->create(['quantity_ordered' => 2]);

        $this->expectException(ValidationException::class);
        app(ReceivePurchase::class)->execute(User::factory()->admin()->create(), $order, [$item->id => 3], 'receipt-00000004');
    }
}
