<?php

namespace Tests\Feature\Shipping;

use App\Enums\OrderStatus;
use App\Enums\ShipmentStatus;
use App\Livewire\Admin\OrderManager;
use App\Models\Order;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ShippingInterfaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_order_list_is_private_and_owner_scoped(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $ownOrder = Order::factory()->for($owner)->create();
        $otherOrder = Order::factory()->for($other)->create();

        $this->get(route('orders.index'))->assertRedirect(route('login'));
        $response = $this->actingAs($owner)->get(route('orders.index'));
        $response->assertOk()->assertSee($ownOrder->public_number)->assertDontSee($otherOrder->public_number);
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
    }

    public function test_admin_route_and_component_enforce_admin_role(): void
    {
        $this->actingAs(User::factory()->create())->get(route('admin.orders'))->assertForbidden();
        $response = $this->actingAs(User::factory()->admin()->create())->get(route('admin.orders'));
        $response->assertOk();
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
    }

    public function test_admin_component_updates_shipping_and_escapes_note(): void
    {
        $admin = User::factory()->admin()->create();
        $shipment = Shipment::factory()->create();
        $unsafe = '<script>alert(1)</script>';

        Livewire::actingAs($admin)->test(OrderManager::class)
            ->set('targetStatus', ShipmentStatus::Preparing->value)
            ->set('note', $unsafe)
            ->call('transition', $shipment->id)
            ->assertHasNoErrors();

        $this->assertSame(ShipmentStatus::Preparing, $shipment->fresh()->status);
        $this->assertSame(OrderStatus::Processing, $shipment->order->fresh()->status);
        $this->actingAs($shipment->order->user)->get(route('orders.show', $shipment->order->public_number))
            ->assertSee($unsafe, true)
            ->assertDontSee($unsafe, false);
    }
}
