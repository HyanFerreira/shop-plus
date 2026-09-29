<?php

namespace Tests\Feature\Payment;

use App\Actions\Cart\AddCartItem;
use App\Actions\Checkout\PlaceOrder;
use App\Actions\Payment\ProcessPayment;
use App\Actions\Payment\RefundOrder;
use App\Domain\Payment\FictitiousCardValidator;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ShipmentStatus;
use App\Enums\StockMovementType;
use App\Models\Address;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class OrderCompletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_expiration_command_releases_reservation_idempotently(): void
    {
        [$user, $order, $inventory] = $this->order(false, 2);
        $order->update(['payment_expires_at' => now()->subMinute()]);

        $this->artisan('orders:expire')->assertSuccessful();
        $this->artisan('orders:expire')->assertSuccessful();

        $this->assertSame(OrderStatus::Cancelled, $order->fresh()->status);
        $this->assertSame(0, $inventory->fresh()->reserved);
        $this->assertSame(1, StockMovement::where('type', StockMovementType::ReservationReleased)->count());
        $this->assertDatabaseHas('audit_logs', ['event' => 'order.expired']);
    }

    public function test_admin_refund_restores_stock_payment_and_shipping_once(): void
    {
        [, $order, $inventory] = $this->order(true, 2);
        $admin = User::factory()->admin()->create();

        app(RefundOrder::class)->execute($admin, $order, 'Devolução acadêmica aprovada');
        app(RefundOrder::class)->execute($admin, $order, 'Devolução acadêmica aprovada');

        $this->assertSame(OrderStatus::Refunded, $order->fresh()->status);
        $this->assertSame(PaymentStatus::Refunded, $order->payments()->first()->status);
        $this->assertSame(5, $inventory->fresh()->on_hand);
        $this->assertSame(ShipmentStatus::Cancelled, $order->shipment->fresh()->status);
        $this->assertSame(1, StockMovement::where('type', StockMovementType::Returned)->count());
    }

    private function order(bool $pay, int $quantity): array
    {
        $user = User::factory()->create();
        $address = Address::factory()->for($user)->create();
        $product = Product::factory()->create();
        $inventory = InventoryItem::factory()->for($product)->create(['on_hand' => 5]);
        app(AddCartItem::class)->execute($user, $product, $quantity);
        $order = app(PlaceOrder::class)->execute($user, $address->id, 'pickup', (string) Str::uuid());
        if ($pay) {
            app(ProcessPayment::class)->execute($user, $order, FictitiousCardValidator::APPROVED_VISA, '123', now()->month, now()->year + 1, (string) Str::uuid());
        }

        return [$user, $order->fresh(), $inventory];
    }
}
