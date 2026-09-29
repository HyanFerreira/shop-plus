<?php

namespace Tests\Feature\Checkout;

use App\Actions\Cart\AddCartItem;
use App\Actions\Checkout\PlaceOrder;
use App\Enums\OrderStatus;
use App\Enums\StockMovementType;
use App\Models\Address;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PlaceOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_freezes_server_values_encrypts_address_and_reserves_stock(): void
    {
        $user = User::factory()->create();
        $address = Address::factory()->for($user)->create();
        $product = Product::factory()->create(['name' => 'Produto FictÃ­cio', 'sku' => 'SKU-TESTE', 'price_cents' => 2500, 'weight_grams' => 800]);
        $inventory = InventoryItem::factory()->for($product)->create(['on_hand' => 10, 'reserved' => 0]);
        app(AddCartItem::class)->execute($user, $product, 2);

        $order = app(PlaceOrder::class)->execute($user, $address->id, 'economy', (string) Str::uuid());

        $this->assertSame(OrderStatus::PendingPayment, $order->status);
        $this->assertSame(5000, $order->subtotal_cents);
        $this->assertSame(6690, $order->total_cents);
        $this->assertSame('Produto FictÃ­cio', $order->items->first()->product_name);
        $this->assertSame(2, $inventory->fresh()->reserved);
        $this->assertSame(StockMovementType::SaleReserved, StockMovement::first()->type);
        $this->assertDatabaseCount('cart_items', 0);
        $this->assertDatabaseCount('order_status_histories', 1);

        $raw = DB::table('orders')->where('id', $order->id)->value('address_snapshot_encrypted');
        $this->assertStringNotContainsString('Rua de Teste Automatizado', $raw);
        $this->assertSame('01001-000', $order->address_snapshot_encrypted['postal_code']);
    }

    public function test_checkout_is_idempotent(): void
    {
        $user = User::factory()->create();
        $address = Address::factory()->for($user)->create();
        $product = Product::factory()->create();
        $inventory = InventoryItem::factory()->for($product)->create(['on_hand' => 5]);
        app(AddCartItem::class)->execute($user, $product, 1);
        $key = (string) Str::uuid();

        $first = app(PlaceOrder::class)->execute($user, $address->id, 'pickup', $key);
        $second = app(PlaceOrder::class)->execute($user, $address->id, 'pickup', $key);

        $this->assertTrue($first->is($second));
        $this->assertSame(1, $inventory->fresh()->reserved);
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('stock_movements', 1);
    }

    public function test_checkout_rejects_another_users_address_and_insufficient_stock(): void
    {
        $user = User::factory()->create();
        $otherAddress = Address::factory()->create();
        $product = Product::factory()->create();
        $inventory = InventoryItem::factory()->for($product)->create(['on_hand' => 2]);
        app(AddCartItem::class)->execute($user, $product, 2);
        $inventory->update(['reserved' => 1]);

        try {
            app(PlaceOrder::class)->execute($user, $otherAddress->id, 'pickup', (string) Str::uuid());
            $this->fail('Expected validation exception.');
        } catch (ValidationException) {
            $this->assertDatabaseCount('orders', 0);
        }

        $address = Address::factory()->for($user)->create();
        $this->expectException(ValidationException::class);
        app(PlaceOrder::class)->execute($user, $address->id, 'pickup', (string) Str::uuid());
    }
}
