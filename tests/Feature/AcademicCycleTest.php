<?php

namespace Tests\Feature;

use App\Actions\Academic\GenerateAcademicCycle;
use App\Actions\Cart\AddCartItem;
use App\Actions\Payment\ProcessPayment;
use App\Enums\OrderStatus;
use App\Models\AcademicCycle;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AcademicCycleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        config(['academic-automation.probabilities' => ['approved' => 100, 'declined' => 0, 'pending' => 0]]);
        $this->travelTo(now()->setDate(2026, 10, 26)->setTime(15, 0));
    }

    public function test_automation_is_off_by_default_but_one_manual_cycle_is_explicitly_allowed(): void
    {
        $this->assertFalse(config('academic-automation.enabled'));
        $this->artisan('academic:cycle')->assertSuccessful();
        $this->assertDatabaseCount('users', 0);
        $this->artisan('academic:cycle --once')->assertSuccessful();
        $this->assertDatabaseCount('users', 1);
        $this->assertTrue(User::first()->is_automated_demo);
        $this->assertFalse((new User)->isFillable('is_automated_demo'));
    }

    public function test_cycle_is_additive_idempotent_and_completes_the_existing_purchase_flow(): void
    {
        $existing = User::factory()->create(['name' => 'Preservado']);
        $action = app(GenerateAcademicCycle::class);
        $first = $action->execute();
        $repeat = $action->execute();
        $this->assertSame($first->id, $repeat->id);
        $this->assertDatabaseCount('users', 2);
        $this->assertDatabaseCount('products', 1);
        $this->assertDatabaseCount('academic_cycles', 1);
        $this->assertSame('Preservado', $existing->fresh()->name);
        $this->assertFalse($existing->fresh()->is_automated_demo);
        $product = Product::first();
        $this->assertCount(1, $product->images);
        Storage::disk('public')->assertExists($product->images->first()->path);
        $this->assertSame(9, $product->inventoryItem->on_hand);
        $this->assertSame(0, $product->inventoryItem->reserved);
        $this->assertSame(OrderStatus::Paid, Order::first()->status);
        $this->assertSame('completed', $first->purchase_status);
        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseCount('shipments', 1);
        $this->actingAs($existing)->get(route('monitoring.users.show', $first->buyer_id))
            ->assertOk()->assertSee(Order::first()->public_number);
        $this->get(route('monitoring.products.show', $product->id))->assertOk()->assertSee(Order::first()->public_number);
        $this->get(route('monitoring.orders.show', $first->order_id))->assertOk()
            ->assertSee('Pagamento autorizado')->assertSee('Venda concluída')->assertSee('Pago')
            ->assertDontSee('auto-'.$first->cycle_key.'@comercio.example.test');
        $this->travel(5)->minutes();
        $action->execute();
        $this->assertDatabaseCount('users', 3);
        $this->assertDatabaseCount('products', 2);
        $this->assertDatabaseCount('payments', 2);
        $this->actingAs($existing)->get('/usuarios')->assertOk()->assertViewHas('automatedUsersCount', 2);
    }

    public function test_unavailable_purchase_keeps_generated_records_without_partial_reservations(): void
    {
        $this->mock(AddCartItem::class)->shouldReceive('execute')->once()->andThrow(ValidationException::withMessages(['product' => 'Indisponível.']));
        $cycle = app(GenerateAcademicCycle::class)->execute();
        $this->assertSame('skipped', $cycle->purchase_status);
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('products', 1);
        $this->assertDatabaseCount('addresses', 0);
        $this->assertDatabaseCount('orders', 0);
        $this->assertSame(10, Product::first()->inventoryItem->on_hand);
        $this->assertSame(0, Product::first()->inventoryItem->reserved);
    }

    public function test_unexpected_failure_rolls_back_and_allows_retry_in_same_window(): void
    {
        $this->mock(AddCartItem::class)->shouldReceive('execute')->once()->andThrow(new \RuntimeException('Failure'));
        try {
            app(GenerateAcademicCycle::class)->execute();
            $this->fail('Expected exception');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Failure', $exception->getMessage());
        }
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('products', 0);
        $this->assertSame(0, AcademicCycle::count());
        $this->app->forgetInstance(AddCartItem::class);
        app(GenerateAcademicCycle::class)->execute();
        $this->assertDatabaseCount('users', 1);
    }

    public function test_late_purchase_rejection_rolls_back_order_cart_address_and_reservation(): void
    {
        $this->mock(ProcessPayment::class)->shouldReceive('execute')->once()
            ->andThrow(ValidationException::withMessages(['payment' => 'Indisponível.']));
        $cycle = app(GenerateAcademicCycle::class)->execute();
        $this->assertSame('skipped', $cycle->purchase_status);
        $this->assertNull($cycle->order_id);
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('products', 1);
        foreach (['orders', 'payments', 'shipments', 'stock_movements', 'cart_items', 'addresses'] as $table) {
            $this->assertDatabaseCount($table, 0);
        }
        $this->assertSame(10, Product::first()->inventoryItem->on_hand);
        $this->assertSame(0, Product::first()->inventoryItem->reserved);
    }

    public function test_scheduler_keeps_expiry_minutely_and_only_one_generator_every_five_minutes(): void
    {
        $events = collect(app(Schedule::class)->events());
        $cycles = $events->filter(fn ($event) => str_contains($event->command ?? '', 'academic:cycle'));
        $this->assertCount(1, $cycles);
        $this->assertSame('*/5 * * * *', $cycles->first()->expression);
        $this->assertTrue($cycles->first()->withoutOverlapping);
        $expiry = $events->first(fn ($event) => str_contains($event->command ?? '', 'orders:expire'));
        $this->assertSame('* * * * *', $expiry->expression);
        config(['academic-automation.enabled' => true]);
        $this->artisan('academic:cycle')->assertSuccessful();
        $this->assertDatabaseCount('users', 1);
    }
}
