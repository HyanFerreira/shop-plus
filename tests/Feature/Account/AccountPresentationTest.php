<?php

namespace Tests\Feature\Account;

use App\Enums\OrderStatus;
use App\Livewire\Account\Overview;
use App\Livewire\PersonalData\Manager;
use App\Models\Address;
use App\Models\Order;
use App\Models\Phone;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AccountPresentationTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_only_owned_recent_orders_and_real_status_counts(): void
    {
        $user = User::factory()->create(['name' => 'Cliente Fictício']);
        $owned = Order::factory()->for($user)->create(['status' => OrderStatus::PendingPayment]);
        Order::factory()->for($user)->create(['status' => OrderStatus::Delivered]);
        $foreign = Order::factory()->create(['public_number' => 'ORD-OUTRO-CLIENTE']);
        $response = $this->actingAs($user)->get('/dashboard')->assertOk()->assertSeeText('Olá, Cliente')
            ->assertSeeText('Pedidos recentes')->assertSee($owned->public_number)->assertDontSee($foreign->public_number)
            ->assertSeeText('Pagamento pendente')->assertSee(route('orders.show', $owned->public_number));
        $this->assertSame(1, substr_count($response->getContent(), '<header'));
        Livewire::actingAs($user)->test(Overview::class)
            ->assertViewHas('ordersCount', 2)->assertViewHas('pendingCount', 1)->assertViewHas('deliveredCount', 1);
    }

    public function test_account_navigation_has_active_destinations_logout_and_admin_only_when_authorized(): void
    {
        $customer = User::factory()->create();
        foreach (['dashboard', 'personal-data.show', 'profile.show', 'orders.index'] as $route) {
            $response = $this->actingAs($customer)->get(route($route))->assertOk()
                ->assertSee('customer-navigation__disclosure', false)->assertSee(route('profile.show'))
                ->assertSee('action="'.route('logout').'"', false)->assertSee('aria-current="page"', false)
                ->assertDontSee(route('admin.dashboard'));
            $this->assertSame(1, substr_count($response->getContent(), '<header'));
            $this->assertSame(1, substr_count($response->getContent(), '<footer'));
        }
        $this->actingAs(User::factory()->admin()->create())->get('/dashboard')->assertOk()
            ->assertSee(route('admin.dashboard'))->assertSeeText('Administração');
        $this->actingAs($customer)->get(route('admin.dashboard'))->assertForbidden();
    }

    public function test_empty_dashboard_has_honest_catalog_destination_without_fake_activity(): void
    {
        $this->actingAs(User::factory()->create())->get('/dashboard')->assertOk()
            ->assertSeeText('Você ainda não tem pedidos')->assertSee(route('catalog.index'))
            ->assertDontSeeText('Laravel')->assertDontSeeText('Pontos de fidelidade');
    }

    public function test_personal_data_keeps_masks_and_associates_errors_with_existing_bindings(): void
    {
        $user = User::factory()->create();
        Phone::factory()->for($user)->create(['number_encrypted' => '11987654321', 'is_primary' => true]);
        Address::factory()->for($user)->create(['label' => 'Casa fictícia', 'postal_code_encrypted' => '01001000']);
        $this->actingAs($user)->get(route('personal-data.show'))->assertOk()->assertSeeText('Principal')
            ->assertSeeText('CEP *****-***')->assertDontSeeText('11987654321')->assertDontSeeText('01001000');
        Livewire::actingAs($user)->test(Manager::class)->set('cpf', '123')->call('saveProfile')
            ->assertHasErrors('cpf')->assertSee('aria-invalid="true"', false)->assertSee('account-error-cpf', false);
    }

    public function test_profile_keeps_existing_jetstream_actions_and_conditions_in_storefront_shell(): void
    {
        $this->actingAs(User::factory()->create())->get(route('profile.show'))->assertOk()
            ->assertSeeText('Perfil e segurança')->assertSeeText('Autenticação de dois fatores')
            ->assertSee('wire:submit="updateProfileInformation"', false)->assertSee('wire:submit="updatePassword"', false)
            ->assertSee('wire:click="confirmLogout"', false)->assertSee('wire:click="confirmUserDeletion"', false)
            ->assertSee('wire:then="enableTwoFactorAuthentication"', false);
    }
}
