<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class AcademicMonitoringTest extends TestCase
{
    use RefreshDatabase;

    private function simulation(User $buyer, Product $product, bool $marked = true): Order
    {
        $order = Order::factory()->create(['user_id' => $buyer->id, 'is_automated_demo' => $marked, 'status' => OrderStatus::Paid, 'total_cents' => 5000]);
        $order->items()->create(['product_id' => $product->id, 'product_name' => $product->name, 'sku' => $product->sku, 'unit_price_cents' => 2500, 'quantity' => 2, 'total_cents' => 5000]);

        return $order;
    }

    public function test_active_customer_and_admin_have_access_but_guest_and_disabled_users_do_not(): void
    {
        $this->get('/monitoramento')->assertRedirect(route('login'));
        foreach ([User::factory()->create(), User::factory()->admin()->create()] as $viewer) {
            $response = $this->actingAs($viewer)->get('/monitoramento')->assertOk()->assertSee('Monitoramento acadêmico');
            $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        }
        $this->actingAs(User::factory()->disabled()->create())->get('/monitoramento')->assertRedirect(route('login'));
    }

    public function test_synthetic_relations_and_aggregates_are_visible_without_private_data(): void
    {
        $buyer = User::factory()->create(['is_automated_demo' => true, 'name' => 'Cliente Automático', 'email' => 'oculto@example.test']);
        $product = Product::factory()->create(['is_automated_demo' => true]);
        $product->images()->create(['path' => 'products/automation/monitor-test.svg', 'alt_text' => 'Produto fictício', 'sort_order' => 0]);
        $order = $this->simulation($buyer, $product);
        $viewer = User::factory()->create();
        $this->actingAs($viewer)->get('/monitoramento')->assertOk()->assertSee('Cliente Automático')->assertDontSee('oculto@example.test')
            ->assertViewHas('records', fn ($records) => $records->first()->simulated_orders_count === 1 && (int) $records->first()->paid_purchases_cents === 5000);
        $this->get('/monitoramento?tab=products')->assertOk()->assertSee($product->name)->assertSee(Storage::disk('public')->url('products/automation/monitor-test.svg'), false)
            ->assertViewHas('records', fn ($records) => (int) $records->first()->sold_quantity === 2 && $records->first()->simulated_orders_count === 1);
        $this->get('/monitoramento?tab=transactions')->assertOk()->assertSee($order->public_number)->assertSee(route('monitoring.orders.show', $order), false);
        $this->get('/monitoramento/usuarios/'.$buyer->id)->assertOk()->assertSee($order->public_number);
        $this->get('/monitoramento/produtos/'.$product->id)->assertOk()->assertSee($order->public_number);
        $response = $this->get('/monitoramento/pedidos/'.$order->id)->assertOk()->assertSee($product->name)->assertSee('Pago')->assertDontSee('oculto@example.test')->assertDontSee('Rua Fict');
        $this->assertNotContains('address_snapshot_encrypted', array_keys($response->viewData('order')->getAttributes()));
    }

    public function test_unmarked_and_mixed_records_are_never_exposed_even_by_direct_identifier(): void
    {
        $privateUser = User::factory()->create(['name' => 'CLIENTE PRIVADO', 'email' => 'privado@example.test']);
        $buyer = User::factory()->create(['is_automated_demo' => true]);
        $privateProduct = Product::factory()->create(['name' => 'PRODUTO MANUAL PRIVADO']);
        $product = Product::factory()->create(['is_automated_demo' => true]);
        $mixedUserOrder = $this->simulation($privateUser, $product);
        $mixedProductOrder = $this->simulation($buyer, $privateProduct);
        $unmarkedOrder = $this->simulation($buyer, $product, false);
        $this->actingAs($buyer);
        foreach (['users', 'products', 'transactions'] as $tab) {
            $this->get('/monitoramento?tab='.$tab)->assertOk()->assertDontSee('CLIENTE PRIVADO')->assertDontSee('privado@example.test')->assertDontSee('PRODUTO MANUAL PRIVADO')->assertDontSee($mixedUserOrder->public_number)->assertDontSee($mixedProductOrder->public_number)->assertDontSee($unmarkedOrder->public_number);
        }
        $this->get('/monitoramento/usuarios/'.$privateUser->id)->assertNotFound();
        $this->get('/monitoramento/produtos/'.$privateProduct->id)->assertNotFound();
        foreach ([$mixedUserOrder, $mixedProductOrder, $unmarkedOrder] as $order) {
            $this->get('/monitoramento/pedidos/'.$order->id)->assertNotFound();
        }
    }

    public function test_tabs_paginate_and_transaction_filters_use_sao_paulo_day_boundaries(): void
    {
        $viewer = User::factory()->create();
        User::factory()->count(21)->create(['is_automated_demo' => true]);
        $buyer = User::factory()->create(['is_automated_demo' => true]);
        $product = Product::factory()->create(['is_automated_demo' => true]);
        $order = $this->simulation($buyer, $product);
        $order->forceFill(['created_at' => '2026-10-26 02:30:00'])->save();
        $this->actingAs($viewer)->get('/monitoramento')->assertOk()->assertViewHas('records', fn ($records) => $records->count() === 20 && $records->total() === 22);
        $this->get('/monitoramento?tab=transactions&client_id='.$buyer->id.'&status=paid&from=2026-10-25&to=2026-10-25')->assertOk()->assertSee($order->public_number)->assertSee('25/10/2026 23:30:00');
        $this->get('/monitoramento?tab=transactions&from=2026-10-26')->assertOk()->assertDontSee($order->public_number);
        $this->get('/monitoramento?tab=transactions&to=2026-10-25')->assertOk()->assertSee($order->public_number);
        $this->get('/monitoramento?tab=transactions&status=cancelled')->assertOk()->assertDontSee($order->public_number);
    }

    public function test_order_details_project_only_safe_payment_fields_and_its_own_stock_movements(): void
    {
        $buyer = User::factory()->create(['is_automated_demo' => true]);
        $product = Product::factory()->create(['is_automated_demo' => true]);
        $order = $this->simulation($buyer, $product);
        $otherOrder = $this->simulation($buyer, $product);
        $payment = $order->payments()->create(['amount_cents' => 5000, 'status' => 'authorized', 'token' => (string) Str::uuid(), 'brand' => 'visa', 'last_four' => '9876', 'authorization_code' => 'PRIVATE-AUTHORIZATION', 'idempotency_key' => (string) Str::uuid(), 'processed_at' => now()]);
        $stock = InventoryItem::factory()->create(['product_id' => $product->id]);
        $own = StockMovement::factory()->create(['inventory_item_id' => $stock->id, 'reference_type' => Order::class, 'reference_id' => $order->id, 'reason' => 'PRIVATE-REASON']);
        $paymentMovement = StockMovement::factory()->create(['inventory_item_id' => $stock->id, 'reference_type' => Payment::class, 'reference_id' => $payment->id]);
        StockMovement::factory()->create(['inventory_item_id' => $stock->id, 'reference_type' => Order::class, 'reference_id' => $otherOrder->id]);
        $response = $this->actingAs($buyer)->get('/monitoramento/pedidos/'.$order->id)->assertOk()->assertDontSee($payment->token)->assertDontSee('PRIVATE-AUTHORIZATION')->assertDontSee('PRIVATE-REASON')->assertDontSee('9876');
        $this->assertEqualsCanonicalizing([$own->id, $paymentMovement->id], $response->viewData('movements')->pluck('id')->all());
        $attributes = array_keys($response->viewData('order')->payments->first()->getAttributes());
        $this->assertEqualsCanonicalizing(['id', 'order_id', 'status', 'amount_cents', 'processed_at'], $attributes);
    }
}
