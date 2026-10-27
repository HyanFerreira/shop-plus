<?php

namespace Tests\Feature;

use App\Actions\Academic\GenerateAcademicCycle;
use App\Actions\Academic\SelectSyntheticPurchase;
use App\Domain\Academic\TransactionOutcomeSelector;
use App\Enums\CatalogStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\StockMovementType;
use App\Enums\UserStatus;
use App\Models\Category;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AcademicRandomizedCycleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->travelTo(now()->setDate(2026, 10, 26)->setTime(15, 0));
    }

    #[DataProvider('outcomes')]
    public function test_each_outcome_uses_existing_order_payment_and_stock_flow(string $outcome, OrderStatus $status, ?PaymentStatus $paymentStatus, int $onHand, int $reserved): void
    {
        $buyer = $this->syntheticBuyer();
        $product = $this->syntheticProduct();
        $this->select($buyer, $product);
        config(['academic-automation.probabilities' => [
            'approved' => $outcome === 'approved' ? 100 : 0,
            'declined' => $outcome === 'declined' ? 100 : 0,
            'pending' => $outcome === 'pending' ? 100 : 0,
        ]]);

        $cycle = app(GenerateAcademicCycle::class)->execute();
        $this->assertSame($outcome, $cycle->outcome);
        $this->assertSame('completed', $cycle->purchase_status);
        $this->assertSame($buyer->id, $cycle->buyer_id);
        $this->assertNotSame($buyer->id, $cycle->user_id);
        $order = Order::findOrFail($cycle->order_id);
        $this->assertSame($buyer->id, $order->user_id);
        $this->assertTrue($order->is_automated_demo);
        $this->assertSame($status, $order->status);
        $this->assertSame($product->id, $order->items()->sole()->product_id);
        $this->assertSame($onHand, $product->inventoryItem->fresh()->on_hand);
        $this->assertSame($reserved, $product->inventoryItem->fresh()->reserved);
        $this->assertSame($paymentStatus ? 1 : 0, $order->payments()->count());
        if ($paymentStatus) {
            $this->assertSame($paymentStatus, $order->payments()->sole()->status);
            $this->assertSame($status, $order->statusHistories()->latest('id')->first()->to_status);
        }
        $this->assertSame($outcome === 'approved' ? 1 : 0, $order->shipment()->count());
        $movement = match ($outcome) {
            'approved' => StockMovementType::SaleCompleted,
            'declined' => StockMovementType::ReservationReleased,
            'pending' => StockMovementType::SaleReserved,
        };
        $this->assertSame($movement, StockMovement::latest('id')->first()->type);
        $this->assertDatabaseCount('users', 2);
        $this->assertDatabaseCount('products', 2);
        $generatedProduct = Product::findOrFail($cycle->product_id);
        $this->assertTrue($generatedProduct->is_automated_demo);
        $this->assertCount(1, $generatedProduct->images);
        Storage::disk('public')->assertExists($generatedProduct->images->sole()->path);
    }

    public static function outcomes(): array
    {
        return [
            ['approved', OrderStatus::Paid, PaymentStatus::Authorized, 9, 0],
            ['declined', OrderStatus::Cancelled, PaymentStatus::Failed, 10, 0],
            ['pending', OrderStatus::PendingPayment, null, 10, 1],
        ];
    }

    public function test_same_synthetic_buyer_can_buy_again_in_later_windows_and_retry_keeps_outcome(): void
    {
        $human = User::factory()->create(['name' => 'Pessoa preservada']);
        $buyer = $this->syntheticBuyer();
        $product = $this->syntheticProduct();
        $this->select($buyer, $product);
        $this->mock(TransactionOutcomeSelector::class)->shouldReceive('choose')->twice()->andReturn('approved', 'declined');
        $first = app(GenerateAcademicCycle::class)->execute();
        $repeat = app(GenerateAcademicCycle::class)->execute();
        $this->assertSame($first->id, $repeat->id);
        $this->assertSame('approved', $repeat->outcome);
        $this->travel(5)->minutes();
        $second = app(GenerateAcademicCycle::class)->execute();
        $this->assertSame('declined', $second->outcome);
        $this->assertSame($buyer->id, $first->buyer_id);
        $this->assertSame($buyer->id, $second->buyer_id);
        $this->assertDatabaseCount('users', 4);
        $this->assertDatabaseCount('products', 3);
        $this->assertSame(2, $buyer->orders()->count());
        $this->assertSame(0, $human->orders()->count());
        $this->assertSame('Pessoa preservada', $human->fresh()->name);
        $this->assertFalse($human->fresh()->is_automated_demo);
        $this->assertSame(9, $product->inventoryItem->fresh()->on_hand);
        $this->assertSame(0, $product->inventoryItem->fresh()->reserved);
        foreach ([$first, $second] as $cycle) {
            $generated = User::findOrFail($cycle->user_id);
            $this->assertTrue($generated->is_automated_demo);
            $this->assertMatchesRegularExpression('/^\p{L}[\p{L}\p{M}\'’-]*(?: [\p{L}\p{M}\'’-]+)+$/u', $generated->name);
        }
        $this->assertFalse((new Product)->isFillable('is_automated_demo'));
        $this->assertFalse((new Order)->isFillable('is_automated_demo'));
    }

    public function test_pending_purchase_expires_without_payment_or_stock_sale(): void
    {
        $buyer = $this->syntheticBuyer();
        $product = $this->syntheticProduct();
        $this->select($buyer, $product);
        $this->mock(TransactionOutcomeSelector::class)->shouldReceive('choose')->once()->andReturn('pending');
        $cycle = app(GenerateAcademicCycle::class)->execute();
        $order = Order::findOrFail($cycle->order_id);
        $this->travelTo($order->payment_expires_at->copy()->addSecond());
        $this->artisan('orders:expire')->assertSuccessful();
        $this->artisan('orders:expire')->assertSuccessful();
        $this->assertSame(OrderStatus::Cancelled, $order->fresh()->status);
        $this->assertSame(10, $product->inventoryItem->fresh()->on_hand);
        $this->assertSame(0, $product->inventoryItem->fresh()->reserved);
        $this->assertSame('pending', $cycle->fresh()->outcome);
        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseCount('shipments', 0);
        $this->assertSame(1, StockMovement::where('type', StockMovementType::ReservationReleased)->count());
    }

    public function test_selection_excludes_human_inactive_deleted_and_unavailable_records(): void
    {
        User::factory()->create();
        $inactiveBuyer = $this->syntheticBuyer();
        $inactiveBuyer->forceFill(['status' => UserStatus::Disabled])->save();
        $deletedBuyer = $this->syntheticBuyer();
        $deletedBuyer->delete();
        $eligibleBuyer = $this->syntheticBuyer();
        $humanProduct = $this->syntheticProduct();
        $humanProduct->forceFill(['is_automated_demo' => false])->save();
        $inactiveProduct = $this->syntheticProduct();
        $inactiveProduct->update(['status' => CatalogStatus::Inactive]);
        $hiddenCategoryProduct = $this->syntheticProduct();
        $hiddenCategoryProduct->category->update(['status' => CatalogStatus::Inactive]);
        $reservedProduct = $this->syntheticProduct();
        $reservedProduct->inventoryItem->update(['reserved' => 10]);
        $deletedProduct = $this->syntheticProduct();
        $deletedProduct->delete();
        $eligibleProduct = $this->syntheticProduct();
        $selector = app(SelectSyntheticPurchase::class);
        $this->assertTrue($eligibleBuyer->is($selector->customer()));
        $this->assertTrue($eligibleProduct->is($selector->product()));
        $eligibleProduct->inventoryItem->update(['on_hand' => 0]);
        $this->assertNull($selector->product());
    }

    private function select(User $buyer, Product $product): void
    {
        $this->mock(SelectSyntheticPurchase::class, function ($mock) use ($buyer, $product): void {
            $mock->shouldReceive('customer')->andReturn($buyer);
            $mock->shouldReceive('product')->andReturn($product);
        });
    }

    private function syntheticBuyer(): User
    {
        $user = User::factory()->create();
        $user->forceFill(['is_automated_demo' => true])->save();

        return $user;
    }

    private function syntheticProduct(): Product
    {
        $product = Product::factory()->for(Category::factory())->create();
        $product->forceFill(['is_automated_demo' => true])->save();
        InventoryItem::factory()->for($product)->create(['on_hand' => 10, 'reserved' => 0]);

        return $product;
    }
}
