<?php

namespace Tests\Feature\Checkout;

use App\Actions\Cart\AddCartItem;
use App\Livewire\CheckoutManager;
use App\Models\Address;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CheckoutPresentationTest extends TestCase
{
    use RefreshDatabase;

    public function test_server_prepares_line_totals_and_estimates_from_existing_shipping_options(): void
    {
        [$user, $product] = $this->cart();
        $address = Address::factory()->for($user)->create(['label' => 'Casa de teste', 'postal_code_encrypted' => '01001000', 'is_primary' => true]);
        $other = Address::factory()->for($user)->create(['label' => 'Outro endereço', 'postal_code_encrypted' => '70000000']);
        $component = Livewire::actingAs($user)->test(CheckoutManager::class)->assertViewHas('subtotal', 20000)
            ->assertViewHas('estimatedTotal', 21490)->assertSeeText('R$ 214,90')->assertSee('wire:model.live="shippingMethod"', false)
            ->assertViewHas('lineTotals', fn ($totals) => array_values($totals) === [20000]);
        $component->set('shippingMethod', 'express')->assertViewHas('estimatedTotal', 22890)
            ->set('shippingMethod', 'pickup')->assertViewHas('estimatedTotal', 20000)
            ->set('shippingMethod', 'economy')->set('addressId', $other->id)->assertViewHas('estimatedTotal', 22490)
            ->assertSeeText('Casa de teste')->assertDontSeeText($address->postal_code_encrypted)
            ->assertDontSeeText($other->postal_code_encrypted);
        $this->assertSame(10000, $product->fresh()->price_cents);
    }

    public function test_checkout_uses_demo_images_and_clear_empty_and_no_address_states(): void
    {
        $this->get(route('checkout.show'))->assertRedirect(route('login'));
        $user = User::factory()->create();
        $this->actingAs($user)->get(route('checkout.show'))->assertOk()->assertSeeText('Seu carrinho está vazio.')
            ->assertSee('checkout-container', false)->assertSee(route('catalog.index'), false);
        [$user] = $this->cart();
        Livewire::actingAs($user)->test(CheckoutManager::class)->assertSee('images/demo-products/phone.svg', false)
            ->assertSeeText('Cadastre um endereço para continuar')->assertSee(route('personal-data.show'), false)
            ->assertViewHas('estimatedTotal', null)->assertDontSee('id="checkout-confirm"', false);
    }

    public function test_presentation_keeps_order_creation_reservation_and_checkout_key(): void
    {
        [$user, $product] = $this->cart();
        $address = Address::factory()->for($user)->create();
        $component = Livewire::actingAs($user)->test(CheckoutManager::class)->set('addressId', $address->id)->set('shippingMethod', 'pickup');
        $key = $component->get('checkoutKey');
        $component->assertSeeText('Criar pedido e reservar estoque')->assertSee('wire:click="confirm"', false)
            ->call('confirm')->assertHasNoErrors()->assertRedirect(route('orders.show', Order::first()->public_number));
        $this->assertSame($key, Order::first()->checkout_key);
        $this->assertSame(2, $product->inventoryItem->fresh()->reserved);
        $this->assertSame('pending_payment', Order::first()->status->value);
    }

    public function test_duplicate_address_labels_are_distinguished_with_a_review_link_without_private_fields(): void
    {
        [$user] = $this->cart();
        Address::factory()->count(2)->for($user)->create(['label' => 'Casa', 'postal_code_encrypted' => '01001000']);
        Livewire::actingAs($user)->test(CheckoutManager::class)->assertSeeText('Casa · cadastro 1')
            ->assertSeeText('Casa · cadastro 2')->assertSeeText('Há endereços com o mesmo nome')
            ->assertSee(route('personal-data.show'), false)->assertDontSeeText('01001000');
    }

    private function cart(): array
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['sku' => 'NIM-PHONE-01', 'price_cents' => 10000, 'weight_grams' => 400]);
        InventoryItem::factory()->for($product)->create(['on_hand' => 10, 'reserved' => 0]);
        app(AddCartItem::class)->execute($user, $product, 2);
        return [$user, $product];
    }
}
