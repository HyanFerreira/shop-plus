<?php

namespace Tests\Feature\Account;

use App\Enums\OrderStatus;
use App\Enums\ShipmentStatus;
use App\Livewire\Account\Overview;
use App\Models\Order;
use App\Models\Product;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardRedesignTest extends TestCase
{
    use RefreshDatabase;

    public function test_summary_counts_all_owned_orders_and_only_owned_favorites_but_limits_recent_orders(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $user->favoriteProducts()->attach(Product::factory()->count(2)->create()->modelKeys());
        $user->favoriteProducts()->attach(Product::factory()->inactive()->create());
        $other->favoriteProducts()->attach(Product::factory()->create());
        foreach (OrderStatus::cases() as $status) {
            Order::factory()->for($user)->create(['status' => $status]);
        }
        $foreign = Order::factory()->for($other)->create();
        Livewire::actingAs($user)->test(Overview::class)
            ->assertViewHas('ordersCount', 7)->assertViewHas('pendingCount', 1)
            ->assertViewHas('deliveredCount', 1)->assertViewHas('favoritesCount', 2)
            ->assertViewHas('recentOrders', function ($orders) use ($user) {
                $this->assertCount(3, $orders);
                $this->assertSame($user->orders()->latest()->orderByDesc('id')->limit(3)->pluck('id')->all(), $orders->modelKeys());
                foreach ($orders as $order) {
                    $this->assertTrue($order->relationLoaded('statusHistories'));
                    $this->assertTrue($order->relationLoaded('shipment'));
                    $this->assertTrue($order->relationLoaded('items'));
                }

                return true;
            })->assertDontSee($foreign->public_number)->assertSee('Favoritos');
    }

    public function test_progress_marks_only_recorded_history_and_keeps_logistics_separate(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->for($user)->create(['status' => OrderStatus::Shipped]);
        $order->statusHistories()->create(['from_status' => OrderStatus::PendingPayment, 'to_status' => OrderStatus::Paid]);
        Shipment::factory()->for($order)->create(['status' => ShipmentStatus::Failed]);
        $html = Livewire::actingAs($user)->test(Overview::class)->assertSee('Falha na entrega')->html();
        $xpath = $this->xpath($html);
        $this->assertSame(3, $xpath->query('//li[@data-step-state="recorded"]')->length);
        $this->assertSame(2, $xpath->query('//li[@data-step-state="unrecorded"]')->length);
        $this->assertSame(1, $xpath->query('//li[@aria-current="step"][contains(., "Enviado")]')->length);
        $this->assertSame(0, $xpath->query('//li[contains(., "Entregue")][@data-step-state="recorded"]')->length);
    }

    public function test_every_order_status_is_presented_without_invented_completed_stages(): void
    {
        $labels = ['pending_payment' => 'Pagamento pendente', 'paid' => 'Pago', 'processing' => 'Em processamento', 'shipped' => 'Enviado', 'delivered' => 'Entregue', 'cancelled' => 'Cancelado', 'refunded' => 'Reembolsado'];
        foreach (OrderStatus::cases() as $status) {
            $user = User::factory()->create();
            Order::factory()->for($user)->create(['status' => $status]);
            $component = Livewire::actingAs($user)->test(Overview::class)->assertSee($labels[$status->value]);
            $xpath = $this->xpath($component->html());
            $recorded = match ($status) {
                OrderStatus::Cancelled, OrderStatus::Refunded => 0,
                OrderStatus::Paid, OrderStatus::Delivered => 2,
                default => 1,
            };
            $this->assertSame($recorded, $xpath->query('//li[@data-step-state="recorded"]')->length);
            $this->assertSame(1, $xpath->query('//li[@aria-current="step"]')->length);
            if (in_array($status, [OrderStatus::Cancelled, OrderStatus::Refunded], true)) {
                $this->assertSame(1, $xpath->query('//ol[contains(@class, "dashboard-progress")]/li')->length);
            }
        }
    }

    public function test_empty_dashboard_has_real_account_destinations_and_zero_favorites(): void
    {
        $user = User::factory()->create(['name' => 'Ana Silva']);
        $this->actingAs($user)->get('/dashboard')->assertOk()->assertSeeText('Olá, Ana')
            ->assertSeeText('Proteção da conta')->assertSeeText('Opções de verificação em duas etapas')
            ->assertSeeText('Sua conta, do seu jeito')->assertSeeText('Você ainda não tem pedidos')
            ->assertSee(route('personal-data.show'))->assertSee(route('profile.show').'#seguranca')
            ->assertSee(route('favorites.index'))->assertSee(route('catalog.index'));
        Livewire::actingAs($user)->test(Overview::class)->assertViewHas('favoritesCount', 0);
    }

    public function test_connector_completion_requires_both_adjacent_milestones_and_real_history(): void
    {
        $stages = [OrderStatus::PendingPayment, OrderStatus::Paid, OrderStatus::Processing, OrderStatus::Shipped, OrderStatus::Delivered];
        foreach ([0 => 0, 1 => 1, 2 => 1, 3 => 2, 4 => 4] as $index => $expected) {
            $user = User::factory()->create();
            $order = Order::factory()->for($user)->create(['status' => $stages[$index]]);
            for ($step = 1; $step <= $index; $step++) {
                $order->statusHistories()->create(['from_status' => $stages[$step - 1], 'to_status' => $stages[$step]]);
            }
            $html = Livewire::actingAs($user)->test(Overview::class)->html();
            $this->assertSame($expected, $this->xpath($html)->query('//li[@data-connector-complete="true"]')->length, $stages[$index]->value);
        }

        $user = User::factory()->create();
        Order::factory()->for($user)->create(['status' => OrderStatus::Delivered]);
        $html = Livewire::actingAs($user)->test(Overview::class)->html();
        $this->assertSame(0, $this->xpath($html)->query('//li[@data-connector-complete="true"]')->length);
    }

    public function test_paid_order_distinguishes_awaited_preparation_from_preparation_in_progress(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->for($user)->create(['status' => OrderStatus::Paid]);
        $shipment = Shipment::factory()->for($order)->create(['status' => ShipmentStatus::AwaitingProcessing]);

        $component = Livewire::actingAs($user)->test(Overview::class)->assertDontSee('Entrega:')->assertDontSee('Aguardando preparação');
        $xpath = $this->xpath($component->html());
        $this->assertSame(2, $xpath->query('//li[@data-step-state="recorded"]//svg')->length);
        $this->assertSame(1, $xpath->query('//li[@data-status="processing"][@data-step-state="next"][@aria-current="step"][contains(., "Aguardando")]')->length);
        $this->assertSame(1, $xpath->query('//li[@data-status="pending_payment"][@data-connector-state="complete"]')->length);
        $this->assertSame(1, $xpath->query('//li[@data-status="paid"][@data-connector-state="focus"]')->length);

        $shipment->update(['status' => ShipmentStatus::Preparing]);
        $xpath = $this->xpath(Livewire::actingAs($user)->test(Overview::class)->html());
        $this->assertSame(1, $xpath->query('//li[@data-status="processing"][@data-step-state="current"][contains(., "Em andamento")]')->length);
        $this->assertSame(0, $xpath->query('//li[@data-step-state="next"]')->length);
    }

    public function test_delivery_exceptions_do_not_promote_an_awaited_stage_to_active_work(): void
    {
        foreach ([ShipmentStatus::Failed, ShipmentStatus::Returned, ShipmentStatus::Cancelled] as $status) {
            $user = User::factory()->create();
            $order = Order::factory()->for($user)->create(['status' => OrderStatus::Paid]);
            Shipment::factory()->for($order)->create(['status' => $status]);
            $xpath = $this->xpath(Livewire::actingAs($user)->test(Overview::class)->html());
            $this->assertSame(0, $xpath->query('//li[@data-step-state="next" or @data-step-state="current"]')->length);
            $this->assertSame(1, $xpath->query('//p[contains(@class, "dashboard-order__shipment")]')->length);
        }
    }

    public function test_cancelled_shipment_preserves_real_preparation_as_interrupted_not_missing_or_completed(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->for($user)->create(['status' => OrderStatus::Processing]);
        $order->statusHistories()->create(['from_status' => OrderStatus::Paid, 'to_status' => OrderStatus::Processing]);
        Shipment::factory()->for($order)->create(['status' => ShipmentStatus::Cancelled]);
        $component = Livewire::actingAs($user)->test(Overview::class)->assertSee('Entrega cancelada');
        $xpath = $this->xpath($component->html());
        $this->assertSame(1, $xpath->query('//li[@data-status="processing"][@data-step-state="interrupted"][@aria-current="step"][contains(., "Interrompido")]')->length);
        $this->assertSame(0, $xpath->query('//li[@data-status="processing"][@data-step-state="unrecorded" or @data-step-state="recorded"]')->length);
        $this->assertSame(1, $xpath->query('//li[@data-status="paid"][@data-connector-state="focus"]')->length);
    }

    private function xpath(string $html): \DOMXPath
    {
        $document = new \DOMDocument;
        @$document->loadHTML('<?xml encoding="utf-8" ?>'.$html);

        return new \DOMXPath($document);
    }
}
