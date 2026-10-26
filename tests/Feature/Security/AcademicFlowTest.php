<?php

namespace Tests\Feature\Security;

use App\Actions\Shipping\TransitionShipment;
use App\Domain\Payment\FictitiousCardValidator;
use App\Enums\OrderStatus;
use App\Enums\ShipmentStatus;
use App\Livewire\CheckoutManager;
use App\Livewire\PaymentForm;
use App\Livewire\PersonalData\Manager;
use App\Livewire\ProductAddToCart;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class AcademicFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_customer_registers_buys_pays_and_tracks_administered_delivery(): void
    {
        $this->get('/')->assertOk();
        $this->post('/register', ['name' => 'Cliente Acadêmico', 'email' => 'academic@example.test',
            'password' => 'Academic-Test-42!', 'password_confirmation' => 'Academic-Test-42!',
            'role' => 'admin', 'status' => 'disabled'])->assertRedirect('/dashboard');
        $user = User::where('email', 'academic@example.test')->firstOrFail();
        $this->assertFalse($user->isAdmin());
        $this->assertTrue($user->isActive());
        $this->assertTrue(Hash::check('Academic-Test-42!', $user->password));
        // A real subsequent HTTP request resolves a fresh guard and database defaults.
        Auth::forgetGuards();
        $this->post('/logout')->assertRedirect('/');
        $this->post('/login', ['email' => $user->email, 'password' => 'Academic-Test-42!'])->assertRedirect('/dashboard');

        Livewire::actingAs($user)->test(Manager::class)
            ->set('cpf', '529.982.247-25')->set('birthDate', '1990-01-01')->call('saveProfile')->assertHasNoErrors()
            ->set('phoneNumber', '(11) 98765-4321')->call('savePhone')->assertHasNoErrors()
            ->set('addressLabel', 'Casa acadêmica')->set('addressRecipient', 'Cliente Acadêmico')
            ->set('addressPostalCode', '01001-000')->set('addressStreet', 'Rua Fictícia')
            ->set('addressNumber', '123')->set('addressDistrict', 'Centro fictício')
            ->set('addressCity', 'São Paulo')->set('addressState', 'SP')->call('saveAddress')->assertHasNoErrors();

        $product = Product::factory()->create(['price_cents' => 2500]);
        $stock = InventoryItem::factory()->for($product)->create(['on_hand' => 5]);
        $this->get('/catalogo')->assertOk()->assertSee($product->name);
        Livewire::test(ProductAddToCart::class, ['product' => $product])->set('quantity', 2)->call('add')->assertNoRedirect()->assertDispatched('cart-updated');
        Livewire::test(CheckoutManager::class)->set('shippingMethod', 'pickup')->call('confirm')
            ->assertRedirect(route('orders.show', Order::firstOrFail()->public_number));
        $order = $user->orders()->firstOrFail();
        $this->assertSame(5000, $order->total_cents);
        Livewire::test(PaymentForm::class, ['order' => $order])
            ->set('cardholderName', 'Cliente Acadêmico')->set('cardNumber', FictitiousCardValidator::APPROVED_VISA)
            ->set('cvv', '123')->call('pay')->assertHasNoErrors()->assertSet('cardNumber', '')->assertSet('cvv', '');
        $this->get('/pedidos/'.$order->public_number)->assertOk()->assertSeeText('Pago');
        $admin = User::factory()->admin()->create();
        foreach ([ShipmentStatus::Preparing, ShipmentStatus::Shipped, ShipmentStatus::OutForDelivery, ShipmentStatus::Delivered] as $status) {
            app(TransitionShipment::class)->execute($admin, $order->shipment()->firstOrFail(), $status, 'Evento acadêmico fictício');
        }
        $this->assertSame(OrderStatus::Delivered, $order->fresh()->status);
        $this->assertSame(3, $stock->fresh()->on_hand);
        $this->assertSame(0, $stock->fresh()->reserved);
        $this->get('/pedidos/'.$order->public_number)->assertOk()->assertSeeText('Entregue');

        $raw = DB::table('customer_profiles')->where('user_id', $user->id)->first();
        $this->assertStringNotContainsString('529.982.247-25', $raw->cpf_encrypted);
        $this->assertSame(64, strlen($raw->cpf_hash));
        $this->assertDatabaseHas('payments', ['order_id' => $order->id, 'amount_cents' => 5000, 'last_four' => '1111']);
        $this->assertDatabaseCount('orders', 1);
    }
}
