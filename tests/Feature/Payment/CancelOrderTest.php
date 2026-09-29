<?php

namespace Tests\Feature\Payment;

use App\Actions\Cart\AddCartItem;
use App\Actions\Checkout\CancelOrder;
use App\Actions\Checkout\PlaceOrder;
use App\Enums\OrderStatus;
use App\Models\Address;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CancelOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_idempotently_cancel_pending_order_and_release_stock(): void
    {
        $user = User::factory()->create();
        $address = Address::factory()->for($user)->create();
        $product = Product::factory()->create();
        $inventory = InventoryItem::factory()->for($product)->create(['on_hand' => 5]);
        app(AddCartItem::class)->execute($user, $product, 2);
        $order = app(PlaceOrder::class)->execute($user, $address->id, 'pickup', (string) Str::uuid());

        app(CancelOrder::class)->execute($user, $order);
        app(CancelOrder::class)->execute($user, $order);

        $this->assertSame(OrderStatus::Cancelled, $order->fresh()->status);
        $this->assertSame(0, $inventory->fresh()->reserved);
        $this->assertDatabaseCount('stock_movements', 2);
        $this->assertDatabaseCount('order_status_histories', 2);
    }

    public function test_non_owner_cannot_cancel_order(): void
    {
        $order = Order::factory()->create();

        $this->expectException(ValidationException::class);
        app(CancelOrder::class)->execute(User::factory()->create(), $order);
    }
}
