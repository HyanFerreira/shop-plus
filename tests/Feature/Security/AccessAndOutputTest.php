<?php

namespace Tests\Feature\Security;

use App\Livewire\Admin\CatalogManager;
use App\Livewire\CheckoutManager;
use App\Livewire\PaymentForm;
use App\Livewire\PersonalData\Manager;
use App\Models\Address;
use App\Models\Order;
use App\Models\Phone;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Tests\TestCase;

class AccessAndOutputTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_and_customers_cannot_open_administrative_routes(): void
    {
        foreach (['/dashboard', '/meus-dados', '/user/profile', '/carrinho', '/checkout', '/pedidos', '/admin', '/admin/catalogo', '/admin/abastecimento', '/admin/pedidos', '/admin/auditoria', '/admin/usuarios'] as $url) {
            $this->get($url)->assertRedirect('/login');
        }
        $this->actingAs(User::factory()->create());
        foreach (['/admin', '/admin/catalogo', '/admin/abastecimento', '/admin/pedidos', '/admin/auditoria', '/admin/usuarios'] as $url) {
            $this->get($url)->assertForbidden();
        }
        $this->actingAs(User::factory()->admin()->create());
        foreach (['/admin', '/admin/catalogo', '/admin/abastecimento', '/admin/pedidos', '/admin/auditoria'] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_livewire_foreign_address_and_phone_ids_do_not_change_data(): void
    {
        $other = User::factory()->create();
        $address = Address::factory()->for($other)->create();
        $phone = Phone::factory()->for($other)->create();
        Livewire::actingAs(User::factory()->create())->test(Manager::class)
            ->call('editAddress', $address->id)->assertHasErrors('addressLabel')
            ->call('deleteAddress', $address->id)->assertHasErrors('addressLabel')
            ->call('editPhone', $phone->id)->assertHasErrors('phoneNumber')
            ->call('deletePhone', $phone->id)->assertHasErrors('phoneNumber');
        $this->assertDatabaseHas('addresses', ['id' => $address->id, 'user_id' => $other->id]);
        $this->assertDatabaseHas('phones', ['id' => $phone->id, 'user_id' => $other->id]);
    }

    public function test_checkout_rejects_foreign_address_property(): void
    {
        Livewire::actingAs(User::factory()->create())->test(CheckoutManager::class)
            ->set('addressId', Address::factory()->create()->id)->call('confirm')->assertHasErrors('checkout');
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_foreign_order_is_inaccessible_by_url_and_component_mount(): void
    {
        $order = Order::factory()->create();
        $user = User::factory()->create();
        $this->actingAs($user)->get('/pedidos/'.$order->public_number)->assertNotFound();
        $this->expectException(ModelNotFoundException::class);
        Livewire::actingAs($user)->test(PaymentForm::class, ['order' => $order]);
    }

    public function test_payment_order_id_cannot_be_changed(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->for($user)->create();
        $component = Livewire::actingAs($user)->test(PaymentForm::class, ['order' => $order]);
        $this->expectException(CannotUpdateLockedPropertyException::class);
        $component->set('orderId', Order::factory()->create()->id);
    }

    public function test_admin_snapshot_replayed_as_customer_cannot_delete_product(): void
    {
        $product = Product::factory()->create();
        $component = Livewire::actingAs(User::factory()->admin()->create())->test(CatalogManager::class);
        $this->actingAs(User::factory()->create());
        $this->postJson(Livewire::getUpdateUri(), ['components' => [[
            'snapshot' => json_encode($component->snapshot), 'updates' => (object) [],
            'calls' => [['method' => 'deleteProduct', 'params' => [$product->id], 'path' => '']],
        ]]], ['X-Livewire' => 'true'])->assertForbidden();
        $this->assertNotSoftDeleted($product);
    }

    public function test_stored_and_reflected_payloads_are_escaped_in_catalog_and_account(): void
    {
        $payload = '<img src=x onerror=alert(1)>';
        $product = Product::factory()->create(['name' => $payload, 'description' => $payload]);
        $this->get('/catalogo/'.$product->slug)->assertOk()->assertSee($payload)->assertDontSee($payload, false);
        $this->get('/catalogo?q='.urlencode($payload))->assertOk()->assertSee($payload)->assertDontSee($payload, false);
        $user = User::factory()->create(['name' => $payload]);
        Address::factory()->for($user)->create(['label' => $payload]);
        Order::factory()->for($user)->create(['public_number' => $payload]);
        foreach (['/dashboard', '/meus-dados', '/pedidos', '/user/profile'] as $url) {
            $this->actingAs($user)->get($url)->assertOk()->assertSee($payload)->assertDontSee($payload, false);
        }
    }

    public function test_sql_injection_search_is_a_literal_bound_value(): void
    {
        Product::factory()->count(2)->create();
        $this->get('/catalogo?q='.urlencode("' OR 1=1 --"))->assertOk()->assertSeeText('Nenhum produto');
        $this->assertDatabaseCount('products', 2);
    }

    public function test_login_rotates_session_and_logout_invalidates_authentication(): void
    {
        $user = User::factory()->create();
        $this->get('/login');
        $oldId = session()->getId();
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
        $this->assertNotSame($oldId, session()->getId());
        $loginId = session()->getId();
        $this->post('/logout')->assertRedirect('/');
        $this->assertGuest();
        $this->assertNotSame($loginId, session()->getId());
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_account_responses_are_not_cacheable(): void
    {
        $this->actingAs(User::factory()->create());
        foreach (['/dashboard', '/user/profile'] as $url) {
            $response = $this->get($url)->assertOk();
            $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
            $this->assertStringContainsString('private', $response->headers->get('Cache-Control'));
        }
    }
}
