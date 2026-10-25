<?php

namespace Tests\Feature\Security;

use App\Actions\Cart\AddCartItem;
use App\Actions\Checkout\PlaceOrder;
use App\Actions\Payment\ProcessPayment;
use App\Domain\Payment\FictitiousCardValidator;
use App\Enums\OrderStatus;
use App\Models\Address;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CommercialIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_repeated_cart_additions_cannot_exceed_limit(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        InventoryItem::factory()->for($product)->create(['on_hand' => 1000]);
        app(AddCartItem::class)->execute($user, $product, 99);
        try {
            app(AddCartItem::class)->execute($user, $product, 1);
            $this->fail('Cumulative cart limit bypassed.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('quantity', $exception->errors());
        }
        $this->assertDatabaseHas('cart_items', ['product_id' => $product->id, 'quantity' => 99]);
    }

    public function test_expired_order_cannot_be_paid_before_scheduler_runs(): void
    {
        $user = User::factory()->create();
        $address = Address::factory()->for($user)->create();
        $product = Product::factory()->create();
        $stock = InventoryItem::factory()->for($product)->create(['on_hand' => 5]);
        app(AddCartItem::class)->execute($user, $product, 1);
        $order = app(PlaceOrder::class)->execute($user, $address->id, 'pickup', (string) Str::uuid());
        $this->travel(31)->minutes();
        try {
            app(ProcessPayment::class)->execute($user, $order, FictitiousCardValidator::APPROVED_VISA, '123', now()->month, now()->year + 1, (string) Str::uuid());
            $this->fail('Expired payment accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('payment', $exception->errors());
        }
        $this->assertDatabaseCount('payments', 0);
        $this->assertSame(5, $stock->fresh()->on_hand);
        $this->assertSame(1, $stock->fresh()->reserved);
    }

    public function test_checkout_key_from_another_customer_is_rejected_without_database_error(): void
    {
        $firstUser = User::factory()->create();
        $secondUser = User::factory()->create();
        $product = Product::factory()->create();
        InventoryItem::factory()->for($product)->create(['on_hand' => 5]);
        $key = (string) Str::uuid();
        foreach ([$firstUser, $secondUser] as $user) {
            $address = Address::factory()->for($user)->create();
            app(AddCartItem::class)->execute($user, $product, 1);
            if ($user->is($firstUser)) {
                app(PlaceOrder::class)->execute($user, $address->id, 'pickup', $key);
            }
        }
        try {
            app(PlaceOrder::class)->execute($secondUser, $address->id, 'pickup', $key);
            $this->fail('Foreign checkout key accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('checkout', $exception->errors());
        }
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('cart_items', 1);
    }

    public function test_two_customers_cannot_reserve_the_same_last_unit(): void
    {
        $product = Product::factory()->create();
        $stock = InventoryItem::factory()->for($product)->create(['on_hand' => 1]);
        $first = User::factory()->create();
        $second = User::factory()->create();
        foreach ([$first, $second] as $user) {
            app(AddCartItem::class)->execute($user, $product, 1);
        }
        app(PlaceOrder::class)->execute($first, Address::factory()->for($first)->create()->id, 'pickup', (string) Str::uuid());
        try {
            app(PlaceOrder::class)->execute($second, Address::factory()->for($second)->create()->id, 'pickup', (string) Str::uuid());
            $this->fail('Last unit reserved twice.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('checkout', $exception->errors());
        }
        $this->assertSame(1, $stock->fresh()->reserved);
        $this->assertDatabaseCount('orders', 1);
    }

    public function test_late_payment_failure_rolls_back_payment_stock_and_history(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        $stock = InventoryItem::factory()->for($product)->create(['on_hand' => 5]);
        app(AddCartItem::class)->execute($user, $product, 1);
        $order = app(PlaceOrder::class)->execute($user, Address::factory()->for($user)->create()->id, 'pickup', (string) Str::uuid());
        Shipment::creating(fn () => throw new \RuntimeException('Synthetic late failure'));
        try {
            app(ProcessPayment::class)->execute($user, $order, FictitiousCardValidator::APPROVED_VISA, '123', now()->month, now()->year + 1, (string) Str::uuid());
            $this->fail('Synthetic failure was not reached.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Synthetic late failure', $exception->getMessage());
        } finally {
            Shipment::flushEventListeners();
        }
        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseCount('shipments', 0);
        $this->assertDatabaseCount('stock_movements', 1);
        $this->assertDatabaseCount('order_status_histories', 1);
        $this->assertSame(5, $stock->fresh()->on_hand);
        $this->assertSame(1, $stock->fresh()->reserved);
        $this->assertSame(OrderStatus::PendingPayment, $order->fresh()->status);
    }
}
