<?php

namespace Tests\Feature\Inventory;

use App\Enums\PurchaseOrderStatus;
use App\Enums\StockMovementType;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\SupplierProduct;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProcurementPersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_supplier_sensitive_fields_are_encrypted_and_hidden(): void
    {
        $supplier = Supplier::factory()->create();
        $raw = DB::table('suppliers')->find($supplier->id);

        foreach (Supplier::ENCRYPTED_FIELDS as $field) {
            $this->assertNotSame($supplier->{$field}, $raw->{$field});
            $this->assertArrayNotHasKey($field, $supplier->toArray());
        }

        $this->assertArrayNotHasKey('document_hash', $supplier->toArray());
    }

    public function test_supplier_product_cost_is_integer_and_relationships_work(): void
    {
        $supplier = Supplier::factory()->create();
        $product = Product::factory()->create();
        $link = SupplierProduct::factory()->for($supplier)->for($product)->create(['cost_cents' => 12_345]);

        $this->assertIsInt($link->cost_cents);
        $this->assertSame(12_345, $link->cost_cents);
        $this->assertTrue($supplier->supplierProducts->contains($link));
        $this->assertTrue($product->supplierProducts->contains($link));
    }

    public function test_purchase_order_has_typed_status_items_and_integer_totals(): void
    {
        $order = PurchaseOrder::factory()->create(['status' => PurchaseOrderStatus::Draft, 'total_cents' => 24_690]);
        $item = PurchaseOrderItem::factory()->for($order)->create([
            'quantity_ordered' => 2,
            'quantity_received' => 0,
            'unit_cost_cents' => 12_345,
        ]);

        $this->assertSame(PurchaseOrderStatus::Draft, $order->status);
        $this->assertIsInt($order->total_cents);
        $this->assertIsInt($item->unit_cost_cents);
        $this->assertTrue($order->items->contains($item));
    }

    public function test_inventory_available_is_derived_and_movements_are_typed(): void
    {
        $inventory = InventoryItem::factory()->create(['on_hand' => 10, 'reserved' => 3]);
        $movement = StockMovement::factory()->for($inventory)->create([
            'type' => StockMovementType::PurchaseReceived,
            'quantity_delta' => 10,
        ]);

        $this->assertSame(7, $inventory->available());
        $this->assertSame(StockMovementType::PurchaseReceived, $movement->type);
        $this->assertTrue($inventory->movements->contains($movement));
    }

    public function test_public_order_numbers_are_unique(): void
    {
        $order = PurchaseOrder::factory()->create();

        $this->expectException(QueryException::class);
        PurchaseOrder::factory()->create(['public_number' => $order->public_number]);
    }

    public function test_stock_idempotency_keys_are_unique(): void
    {
        $movement = StockMovement::factory()->create();

        $this->expectException(QueryException::class);
        StockMovement::factory()->create(['idempotency_key' => $movement->idempotency_key]);
    }
}
