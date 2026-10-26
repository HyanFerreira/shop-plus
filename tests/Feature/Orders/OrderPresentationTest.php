<?php

namespace Tests\Feature\Orders;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ShipmentStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OrderPresentationTest extends TestCase
{
    use RefreshDatabase;

    public function test_list_has_own_orders_pagination_counts_and_real_customer_destinations(): void
    {
        $user = User::factory()->create();
        $other = Order::factory()->create(['public_number' => 'ORD-OUTRO-CLIENTE']);
        $orders = Order::factory()->count(11)->for($user)->create(['status' => OrderStatus::Paid]);
        $this->item($orders->first(), ['quantity' => 2]);
        $this->item($orders->first(), ['product_name' => 'Segundo item histórico']);
        $response = $this->actingAs($user)->get(route('orders.index'))->assertOk()
            ->assertSeeText('Pago')->assertDontSee($other->public_number)
            ->assertSee('page=2', false)->assertSee(route('profile.show'))->assertDontSee('<aside', false)
            ->assertSee('aria-current="page"', false);
        $this->assertCount(10, $response->viewData('orders')->items());
        $page2 = $this->get(route('orders.index', ['page' => 2]))->assertOk();
        $this->assertCount(1, $page2->viewData('orders')->items());
        $all = $response->getContent().$page2->getContent();
        $this->assertStringContainsString('2 itens', $all);
        $this->assertStringContainsString('3 unidades', $all);
        $this->assertStringContainsString('e mais 1 item', $all);
    }

    public function test_empty_orders_offer_catalog_and_no_fake_status_tabs(): void
    {
        $this->actingAs(User::factory()->create())->get(route('orders.index'))->assertOk()
            ->assertSeeText('Você ainda não tem pedidos')->assertSee(route('catalog.index'))
            ->assertDontSee('role="tab"', false);
    }

    public function test_historical_values_and_sku_drive_display_even_when_product_changes_or_is_deleted(): void
    {
        Storage::fake('public');
        $order = Order::factory()->create(['status' => OrderStatus::Paid, 'subtotal_cents' => 12345, 'shipping_cents' => 1200, 'total_cents' => 13545]);
        $product = Product::factory()->create(['name' => 'Nome novo do catálogo', 'sku' => 'SKU-NOVO', 'price_cents' => 999999]);
        Storage::disk('public')->put('novo.svg', '<svg/>');
        $product->images()->create(['path' => 'novo.svg', 'alt_text' => 'Imagem nova', 'sort_order' => 0]);
        $this->item($order, ['product_id' => $product->id, 'product_name' => 'Nome histórico', 'sku' => 'SKU-ANTIGO', 'unit_price_cents' => 12345, 'total_cents' => 12345]);
        $this->actingAs($order->user)->get(route('orders.show', $order->public_number))->assertOk()
            ->assertSeeText('Nome histórico')->assertSeeText('SKU-ANTIGO')->assertSeeText('R$ 123,45')->assertSeeText('R$ 135,45')
            ->assertDontSee('Nome novo do catálogo')->assertDontSee('novo.svg')->assertSeeText('Imagem indisponível');
        $product->delete();
        $this->get(route('orders.show', $order->public_number))->assertOk()->assertSeeText('Nome histórico');
    }

    public function test_registered_image_then_historical_demo_then_unavailable_are_used(): void
    {
        Storage::fake('public');
        $order = Order::factory()->create(['status' => OrderStatus::Paid]);
        $sku = array_key_first(config('demo-images.products'));
        $product = Product::factory()->create(['sku' => $sku]);
        Storage::disk('public')->put('original.svg', '<svg/>');
        $product->images()->create(['path' => 'original.svg', 'sort_order' => 0]);
        $this->item($order, ['product_id' => $product->id, 'sku' => $sku]);
        $this->actingAs($order->user)->get(route('orders.show', $order->public_number))->assertOk()->assertSee('original.svg');
        $product->delete();
        $this->get(route('orders.show', $order->public_number))->assertOk()
            ->assertSee('images/demo-products/'.config('demo-images.products')[$sku]['file']);
    }

    public function test_every_order_status_has_human_label_and_no_payment_form_except_pending(): void
    {
        $labels = ['pending_payment' => 'Pagamento pendente', 'paid' => 'Pago', 'processing' => 'Em processamento', 'shipped' => 'Enviado', 'delivered' => 'Entregue', 'cancelled' => 'Cancelado', 'refunded' => 'Reembolsado'];
        $user = User::factory()->create();
        foreach (OrderStatus::cases() as $status) {
            $order = Order::factory()->for($user)->create(['status' => $status]);
            $response = $this->actingAs($user)->get(route('orders.show', $order->public_number))->assertOk()->assertSeeText($labels[$status->value]);
            $this->assertSame($status === OrderStatus::PendingPayment ? 1 : 0, substr_count($response->getContent(), 'wire:submit="pay"'));
        }
    }

    public function test_tracking_uses_event_evidence_not_an_assumed_complete_path_and_orders_events(): void
    {
        $shipment = Shipment::factory()->create(['status' => ShipmentStatus::Shipped, 'shipped_at' => now()]);
        $shipment->events()->make(['to_status' => ShipmentStatus::Shipped, 'note' => 'Evento posterior'])->forceFill(['created_at' => '2026-10-24 12:00:00'])->save();
        $shipment->events()->make(['to_status' => ShipmentStatus::Preparing, 'note' => 'Evento anterior'])->forceFill(['created_at' => '2026-10-23 09:00:00'])->save();
        $response = $this->actingAs($shipment->order->user)->get(route('orders.show', $shipment->order->public_number))->assertOk()
            ->assertSee('aria-current="step"', false)->assertSeeText('Sem registro')->assertSeeText('Ainda não realizada')
            ->assertSeeInOrder(['Evento anterior', 'Evento posterior'])->assertSeeText('23/10/2026 09:00')
            ->assertSee($shipment->tracking_code);
        $this->assertSame(1, substr_count($response->getContent(), 'data-step-state="complete"'));
        $this->assertSame(2, substr_count($response->getContent(), 'data-step-state="future"'));
    }

    public function test_exceptional_shipments_and_recovery_do_not_fake_delivery_completion(): void
    {
        $labels = ['failed' => 'Falha na entrega', 'returned' => 'Devolvida', 'cancelled' => 'Entrega cancelada'];
        foreach ([ShipmentStatus::Failed, ShipmentStatus::Returned, ShipmentStatus::Cancelled] as $status) {
            $shipment = Shipment::factory()->create(['status' => $status]);
            $shipment->events()->create(['from_status' => ShipmentStatus::Shipped, 'to_status' => $status, 'note' => 'Ocorrência registrada']);
            $response = $this->actingAs($shipment->order->user)->get(route('orders.show', $shipment->order->public_number))->assertOk()
                ->assertSeeText($labels[$status->value])->assertSeeText('Ocorrência registrada');
            $this->assertStringNotContainsString('aria-current="step"', $response->getContent());
            $this->assertStringNotContainsString('data-step-state="complete"', $response->getContent());
        }
        $shipment = Shipment::factory()->create(['status' => ShipmentStatus::Preparing]);
        foreach ([
            [ShipmentStatus::Preparing, ShipmentStatus::Shipped, 'Envio inicial'],
            [ShipmentStatus::Shipped, ShipmentStatus::OutForDelivery, 'Primeira tentativa'],
            [ShipmentStatus::OutForDelivery, ShipmentStatus::Failed, 'Tentativa sem sucesso'],
            [ShipmentStatus::Failed, ShipmentStatus::Preparing, 'Nova preparação'],
        ] as [$from, $to, $note]) {
            $shipment->events()->create(['from_status' => $from, 'to_status' => $to, 'note' => $note]);
        }
        $response = $this->actingAs($shipment->order->user)->get(route('orders.show', $shipment->order->public_number))->assertOk()
            ->assertSeeText('Registro anterior')->assertSeeInOrder(['Envio inicial', 'Primeira tentativa', 'Tentativa sem sucesso', 'Nova preparação']);
        $this->assertSame(1, substr_count($response->getContent(), 'aria-current="step"'));
        $this->assertStringNotContainsString('data-step-state="complete"', $response->getContent());
        $this->assertMatchesRegularExpression('/data-step-state="current"[^>]*>.*?Em preparação/s', $response->getContent());
    }

    public function test_missing_shipment_and_missing_events_are_explicit_without_invented_dates(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::Cancelled]);
        $this->actingAs($order->user)->get(route('orders.show', $order->public_number))->assertOk()->assertSeeText('Sem registro de expedição');
        $shipment = Shipment::factory()->for($order)->create(['status' => ShipmentStatus::OutForDelivery]);
        $this->get(route('orders.show', $order->public_number))->assertOk()->assertSeeText('Nenhuma movimentação registrada')
            ->assertSeeText('Data de entrega não informada')->assertDontSee('transportadora.com');
    }

    public function test_all_logistics_states_keep_current_step_distinct_and_delivered_requires_real_status(): void
    {
        foreach (ShipmentStatus::cases() as $status) {
            $shipment = Shipment::factory()->create(['status' => $status]);
            $response = $this->actingAs($shipment->order->user)->get(route('orders.show', $shipment->order->public_number))->assertOk();
            $expected = in_array($status, [ShipmentStatus::Failed, ShipmentStatus::Returned, ShipmentStatus::Cancelled], true) ? 0 : 1;
            $this->assertSame($expected, substr_count($response->getContent(), 'aria-current="step"'));
            $this->assertStringNotContainsString('data-step-state="complete"', $response->getContent());
        }
        $shipment->update(['status' => ShipmentStatus::Delivered, 'delivered_at' => '2026-10-24 10:00:00']);
        foreach ([ShipmentStatus::AwaitingProcessing, ShipmentStatus::Preparing, ShipmentStatus::Shipped, ShipmentStatus::OutForDelivery, ShipmentStatus::Delivered] as $status) {
            $shipment->events()->create(['to_status' => $status]);
        }
        $response = $this->get(route('orders.show', $shipment->order->public_number))->assertOk()->assertSeeText('Entrega registrada:')
            ->assertSeeText('24/10/2026 10:00');
        $this->assertSame(4, substr_count($response->getContent(), 'data-step-state="complete"'));
    }

    public function test_payment_is_masked_order_history_is_separate_and_private_routes_stay_isolated(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::Refunded]);
        $order->payments()->create(['amount_cents' => 2500, 'status' => PaymentStatus::Refunded, 'brand' => 'visa', 'last_four' => '1111', 'token' => 'SEGREDO-TOKEN', 'idempotency_key' => 'SEGREDO-IDEMPOTENCIA', 'authorization_code' => 'SEGREDO-AUTORIZACAO', 'processed_at' => now()]);
        $order->statusHistories()->create(['from_status' => OrderStatus::Paid, 'to_status' => OrderStatus::Refunded, 'note' => 'Reembolso fictício registrado']);
        $response = $this->actingAs($order->user)->get(route('orders.show', $order->public_number))->assertOk()->assertSeeText('final 1111')
            ->assertSeeText('Histórico do pedido')->assertSeeText('Reembolso fictício registrado')->assertDontSee('SEGREDO-')
            ->assertHeader('Cache-Control');
        $this->assertTrue($response->headers->hasCacheControlDirective('no-store'));
        $this->assertTrue($response->headers->hasCacheControlDirective('private'));
        $this->actingAs(User::factory()->create())->get(route('orders.show', $order->public_number))->assertNotFound();
        $this->app['auth']->forgetGuards();
        $this->app['auth']->shouldUse('web');
        $this->get(route('orders.index'))->assertRedirect(route('login'));
        $this->get(route('orders.show', $order->public_number))->assertRedirect(route('login'));
    }

    private function item(Order $order, array $attributes = []): void
    {
        $order->items()->create(array_merge(['product_id' => Product::factory()->create()->id, 'product_name' => 'Item histórico', 'sku' => 'SKU-HISTORICO', 'unit_price_cents' => 2500, 'quantity' => 1, 'total_cents' => 2500], $attributes));
    }
}
