<?php

namespace Tests\Feature\Inventory;

use App\Actions\Inventory\CancelPurchaseOrder;
use App\Actions\Inventory\PlacePurchaseOrder;
use App\Actions\Inventory\SavePurchaseOrder;
use App\Enums\PurchaseOrderStatus;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\SupplierProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class PurchaseOrderManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_draft_uses_server_side_supplier_costs_and_computes_total(): void
    {
        $admin = User::factory()->admin()->create();
        $supplier = Supplier::factory()->create();
        $first = Product::factory()->create();
        $second = Product::factory()->create();
        SupplierProduct::factory()->for($supplier)->for($first)->create(['cost_cents' => 1_500]);
        SupplierProduct::factory()->for($supplier)->for($second)->create(['cost_cents' => 2_000]);

        $order = app(SavePurchaseOrder::class)->execute($admin, null, $supplier->id, [
            $first->id => 2,
            $second->id => 3,
        ]);

        $this->assertSame(PurchaseOrderStatus::Draft, $order->status);
        $this->assertSame(9_000, $order->total_cents);
        $this->assertSame(1_500, $order->items->firstWhere('product_id', $first->id)->unit_cost_cents);
        $this->assertStringStartsWith('PO-', $order->public_number);
    }

    public function test_only_draft_orders_can_be_edited_and_placed(): void
    {
        $admin = User::factory()->admin()->create();
        $supplier = Supplier::factory()->create();
        $product = Product::factory()->create();
        SupplierProduct::factory()->for($supplier)->for($product)->create();
        $order = app(SavePurchaseOrder::class)->execute($admin, null, $supplier->id, [$product->id => 2]);

        app(PlacePurchaseOrder::class)->execute($admin, $order);
        $this->assertSame(PurchaseOrderStatus::Placed, $order->fresh()->status);
        $this->assertNotNull($order->fresh()->placed_at);

        $this->expectException(ValidationException::class);
        app(SavePurchaseOrder::class)->execute($admin, $order->id, $supplier->id, [$product->id => 4]);
    }

    public function test_placed_order_can_be_cancelled_but_received_order_cannot(): void
    {
        $admin = User::factory()->admin()->create();
        $placed = PurchaseOrder::factory()->create(['status' => PurchaseOrderStatus::Placed]);
        app(CancelPurchaseOrder::class)->execute($admin, $placed);
        $this->assertSame(PurchaseOrderStatus::Cancelled, $placed->fresh()->status);

        $received = PurchaseOrder::factory()->create(['status' => PurchaseOrderStatus::Received]);
        $this->expectException(ValidationException::class);
        app(CancelPurchaseOrder::class)->execute($admin, $received);
    }

    public function test_customer_cannot_create_purchase_order(): void
    {
        $this->expectException(HttpException::class);
        app(SavePurchaseOrder::class)->execute(User::factory()->create(), null, Supplier::factory()->create()->id, []);
    }
}
