<?php

namespace Tests\Feature\Shipping;

use App\Actions\Shipping\TransitionShipment;
use App\Enums\OrderStatus;
use App\Enums\ShipmentStatus;
use App\Models\Shipment;
use App\Models\ShipmentEvent;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ShipmentLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_advances_delivery_and_order_with_complete_history(): void
    {
        $admin = User::factory()->admin()->create();
        $shipment = Shipment::factory()->create();
        $shipment->events()->create(['to_status' => ShipmentStatus::AwaitingProcessing]);

        app(TransitionShipment::class)->execute($admin, $shipment, ShipmentStatus::Preparing, 'Separação fictícia');
        app(TransitionShipment::class)->execute($admin, $shipment, ShipmentStatus::Shipped, 'Postado');
        app(TransitionShipment::class)->execute($admin, $shipment, ShipmentStatus::OutForDelivery, null);
        app(TransitionShipment::class)->execute($admin, $shipment, ShipmentStatus::Delivered, 'Entregue');

        $this->assertSame(ShipmentStatus::Delivered, $shipment->fresh()->status);
        $this->assertNotNull($shipment->fresh()->shipped_at);
        $this->assertNotNull($shipment->fresh()->delivered_at);
        $this->assertSame(OrderStatus::Delivered, $shipment->order->fresh()->status);
        $this->assertDatabaseCount('shipment_events', 5);
        $this->assertDatabaseCount('order_status_histories', 3);
    }

    public function test_invalid_transition_is_rejected(): void
    {
        $this->expectException(ValidationException::class);
        app(TransitionShipment::class)->execute(User::factory()->admin()->create(), Shipment::factory()->create(), ShipmentStatus::Delivered, null);
    }

    public function test_customer_cannot_change_delivery(): void
    {
        $this->expectException(AuthorizationException::class);
        app(TransitionShipment::class)->execute(User::factory()->create(), Shipment::factory()->create(), ShipmentStatus::Preparing, null);
    }

    public function test_shipment_events_are_immutable(): void
    {
        $event = ShipmentEvent::factory()->for(Shipment::factory())->create(['to_status' => ShipmentStatus::Preparing]);

        try {
            $event->update(['note' => 'alterado']);
            $this->fail('Update should fail.');
        } catch (\LogicException) {
            $this->assertTrue(true);
        }

        $this->expectException(\LogicException::class);
        $event->delete();
    }
}
