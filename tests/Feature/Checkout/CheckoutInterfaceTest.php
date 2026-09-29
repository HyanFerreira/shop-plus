<?php

namespace Tests\Feature\Checkout;

use App\Actions\Cart\AddCartItem;
use App\Livewire\CartManager;
use App\Livewire\CheckoutManager;
use App\Livewire\ProductAddToCart;
use App\Models\Address;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CheckoutInterfaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_cart_and_checkout_routes_require_authentication(): void
    {
        $this->get(route('cart.show'))->assertRedirect(route('login'));
        $this->get(route('checkout.show'))->assertRedirect(route('login'));
    }

    public function test_product_component_adds_to_authenticated_users_cart(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        InventoryItem::factory()->for($product)->create(['on_hand' => 4]);

        Livewire::actingAs($user)->test(ProductAddToCart::class, ['product' => $product])
            ->set('quantity', 2)
            ->call('add')
            ->assertRedirect(route('cart.show'));

        $this->assertDatabaseHas('cart_items', ['product_id' => $product->id, 'quantity' => 2]);
    }

    public function test_cart_component_updates_only_owned_items(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        InventoryItem::factory()->for($product)->create(['on_hand' => 5]);
        $item = app(AddCartItem::class)->execute($user, $product, 1);

        Livewire::actingAs($user)->test(CartManager::class)
            ->set('quantities.'.$item->id, 3)
            ->call('updateItem', $item->id)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('cart_items', ['id' => $item->id, 'quantity' => 3]);
    }

    public function test_checkout_component_creates_order_and_redirects(): void
    {
        $user = User::factory()->create();
        $address = Address::factory()->for($user)->create();
        $product = Product::factory()->create();
        InventoryItem::factory()->for($product)->create(['on_hand' => 5]);
        app(AddCartItem::class)->execute($user, $product, 1);

        Livewire::actingAs($user)->test(CheckoutManager::class)
            ->set('addressId', $address->id)
            ->set('shippingMethod', 'pickup')
            ->call('confirm')
            ->assertRedirect(route('orders.show', Order::first()->public_number));
    }

    public function test_order_details_are_private_owner_scoped_and_not_cacheable(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $order = Order::factory()->for($owner)->create();

        $this->actingAs($other)->get(route('orders.show', $order->public_number))->assertNotFound();
        $response = $this->actingAs($owner)->get(route('orders.show', $order->public_number));
        $response->assertOk()->assertSee($order->public_number);
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
    }
}
