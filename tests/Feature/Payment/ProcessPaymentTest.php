<?php

namespace Tests\Feature\Payment;

use App\Actions\Cart\AddCartItem;
use App\Actions\Checkout\PlaceOrder;
use App\Actions\Payment\ProcessPayment;
use App\Domain\Payment\FictitiousCardValidator;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
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

class ProcessPaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_approval_converts_reservation_to_sale_without_storing_sensitive_card_data(): void
    {
        [$user, $order, $inventory] = $this->pendingOrder(2);
        $key = (string) Str::uuid();

        $payment = app(ProcessPayment::class)->execute($user, $order, FictitiousCardValidator::APPROVED_VISA, '123', now()->month, now()->year + 1, $key);

        $this->assertSame(PaymentStatus::Authorized, $payment->status);
        $this->assertSame('1111', $payment->last_four);
        $this->assertSame(OrderStatus::Paid, $order->fresh()->status);
        $this->assertSame(3, $inventory->fresh()->on_hand);
        $this->assertSame(0, $inventory->fresh()->reserved);
        $this->assertSame(StockMovementType::SaleCompleted, StockMovement::latest('id')->first()->type);

        $raw = json_encode(DB::table('payments')->first());
        $this->assertStringNotContainsString(FictitiousCardValidator::APPROVED_VISA, $raw);
        $this->assertArrayNotHasKey('card_number', $payment->getAttributes());
        $this->assertArrayNotHasKey('cvv', $payment->getAttributes());
    }

    public function test_decline_releases_reservation_and_cancels_order(): void
    {
        [$user, $order, $inventory] = $this->pendingOrder(2);

        $payment = app(ProcessPayment::class)->execute($user, $order, FictitiousCardValidator::DECLINED_VISA, '999', now()->month, now()->year + 1, (string) Str::uuid());

        $this->assertSame(PaymentStatus::Failed, $payment->status);
        $this->assertSame(OrderStatus::Cancelled, $order->fresh()->status);
        $this->assertSame(5, $inventory->fresh()->on_hand);
        $this->assertSame(0, $inventory->fresh()->reserved);
        $this->assertSame(StockMovementType::ReservationReleased, StockMovement::latest('id')->first()->type);
    }

    public function test_same_payment_key_is_idempotent(): void
    {
        [$user, $order, $inventory] = $this->pendingOrder();
        $key = (string) Str::uuid();

        $first = app(ProcessPayment::class)->execute($user, $order, FictitiousCardValidator::APPROVED_MASTERCARD, '123', now()->month, now()->year + 1, $key);
        $second = app(ProcessPayment::class)->execute($user, $order, FictitiousCardValidator::APPROVED_MASTERCARD, '123', now()->month, now()->year + 1, $key);

        $this->assertTrue($first->is($second));
        $this->assertSame(4, $inventory->fresh()->on_hand);
        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseCount('stock_movements', 2);
    }

    public function test_another_user_cannot_pay_order(): void
    {
        [, $order] = $this->pendingOrder();

        $this->expectException(ValidationException::class);
        app(ProcessPayment::class)->execute(User::factory()->create(), $order, FictitiousCardValidator::APPROVED_VISA, '123', now()->month, now()->year + 1, (string) Str::uuid());
    }

    private function pendingOrder(int $quantity = 1): array
    {
        $user = User::factory()->create();
        $address = Address::factory()->for($user)->create();
        $product = Product::factory()->create();
        $inventory = InventoryItem::factory()->for($product)->create(['on_hand' => 5]);
        app(AddCartItem::class)->execute($user, $product, $quantity);
        $order = app(PlaceOrder::class)->execute($user, $address->id, 'pickup', (string) Str::uuid());

        return [$user, $order, $inventory];
    }
}
