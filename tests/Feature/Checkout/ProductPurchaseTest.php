<?php

namespace Tests\Feature\Checkout;

use App\Actions\Cart\AddCartItem;
use App\Actions\Payment\ProcessPayment;
use App\Domain\Payment\FictitiousCardValidator;
use App\Livewire\CheckoutManager;
use App\Livewire\HeaderCart;
use App\Livewire\ProductAddToCart;
use App\Models\Address;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Tests\TestCase;

class ProductPurchaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_detail_reports_sellable_stock_and_keeps_sold_out_products_visible(): void
    {
        $product = $this->product();
        $this->get(route('catalog.show', $product->slug))->assertOk()->assertSeeText('Em estoque: 3 unidades');
        $product->inventoryItem->update(['reserved' => 5]);
        $this->get(route('catalog.show', $product->slug))->assertOk()->assertSeeText('Esgotado');
        $this->get(route('catalog.index'))->assertOk()->assertSee($product->name);
        $component = Livewire::test(ProductAddToCart::class, ['product' => $product]);
        $dom = new \DOMDocument;
        @$dom->loadHTML($component->html());
        foreach (['product-quantity', 'product-quantity-decrement', 'product-quantity-increment', 'product-add-submit', 'product-buy-now'] as $id) {
            $this->assertTrue($dom->getElementById($id)?->hasAttribute('disabled') ?? false, $id);
        }
    }

    public function test_quantity_buttons_obey_stock_and_add_notifies_header_without_redirect(): void
    {
        $product = $this->product();
        $user = User::factory()->create();
        Livewire::actingAs($user)->test(ProductAddToCart::class, ['product' => $product])
            ->call('decrement')->assertSet('quantity', 1)
            ->call('increment')->call('increment')->call('increment')->assertSet('quantity', 3)
            ->call('add')->assertHasNoErrors()->assertNoRedirect()->assertDispatched('cart-updated');
        Livewire::test(HeaderCart::class)->assertViewHas('quantity', 3);
    }

    public function test_invalid_manual_quantities_never_create_cart_items_or_buy_now_intents(): void
    {
        $user = User::factory()->create();
        $product = $this->product();
        foreach (['', -1, 0, '1.5', 'abc', '999999999999999999999999', 4, 100] as $quantity) {
            foreach (['add', 'buyNow'] as $method) {
                Livewire::actingAs($user)->test(ProductAddToCart::class, ['product' => $product])
                    ->set('quantity', $quantity)->call($method)->assertHasErrors('quantity')->assertNoRedirect();
            }
        }
        $this->assertDatabaseCount('cart_items', 0);
        $this->assertEmpty(session('buy-now', []));
    }

    public function test_buy_now_uses_only_selected_quantity_and_preserves_cart_with_idempotent_order(): void
    {
        $user = User::factory()->create();
        Address::factory()->for($user)->create(['is_primary' => true]);
        $product = $this->product();
        $other = $this->product();
        app(AddCartItem::class)->execute($user, $other, 1);
        app(AddCartItem::class)->execute($user, $product, 1);
        $purchase = Livewire::actingAs($user)->test(ProductAddToCart::class, ['product' => $product])
            ->set('quantity', 2)->call('buyNow')->assertHasNoErrors();
        $token = array_key_first(session('buy-now'));
        $purchase->assertRedirect(route('checkout.show', ['buy_now' => $token]));
        $checkout = Livewire::withQueryParams(['buy_now' => $token])->test(CheckoutManager::class)
            ->assertSeeText($product->name)->assertDontSeeText($other->name)
            ->assertViewHas('subtotal', $product->price_cents * 2)->set('shippingMethod', 'pickup');
        $checkout->call('confirm')->assertHasNoErrors();
        $checkout->call('confirm')->assertHasNoErrors();
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('order_items', 1);
        $this->assertDatabaseHas('order_items', ['product_id' => $product->id, 'quantity' => 2]);
        $this->assertDatabaseHas('cart_items', ['product_id' => $other->id, 'quantity' => 1]);
        $this->assertDatabaseHas('cart_items', ['product_id' => $product->id, 'quantity' => 1]);
        $this->assertSame(4, $product->inventoryItem->fresh()->reserved);
        $this->get(route('orders.show', Order::first()->public_number))->assertOk()->assertSeeText('Pagamento');
        app(ProcessPayment::class)->execute($user, Order::first(), FictitiousCardValidator::APPROVED_VISA, '123', now()->month, now()->year + 1, (string) Str::uuid());
        $this->assertSame('paid', Order::first()->status->value);
        $this->assertSame(3, $product->inventoryItem->fresh()->on_hand);
        $this->assertSame(2, $product->inventoryItem->fresh()->reserved);
        $this->assertDatabaseCount('cart_items', 2);
    }

    public function test_stock_lost_after_render_rejects_both_purchase_actions(): void
    {
        $product = $this->product();
        $component = Livewire::actingAs(User::factory()->create())->test(ProductAddToCart::class, ['product' => $product]);
        $product->inventoryItem->update(['reserved' => 5]);
        $component->call('add')->assertHasErrors('quantity')->assertNotDispatched('cart-updated');
        $component->call('buyNow')->assertHasErrors('quantity')->assertNoRedirect();
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_competing_buy_now_checkouts_cannot_reserve_the_same_stock(): void
    {
        $product = $this->product();
        $intents = [];
        foreach (range(1, 2) as $unused) {
            $user = User::factory()->create();
            Address::factory()->for($user)->create(['is_primary' => true]);
            Livewire::actingAs($user)->test(ProductAddToCart::class, ['product' => $product])->set('quantity', 3)->call('buyNow');
            $intents[] = [$user, array_key_last(session('buy-now'))];
        }
        foreach ($intents as $index => [$user, $token]) {
            $checkout = Livewire::actingAs($user)->withQueryParams(['buy_now' => $token])->test(CheckoutManager::class)->set('shippingMethod', 'pickup')->call('confirm');
            $index === 0 ? $checkout->assertHasNoErrors() : $checkout->assertHasErrors('checkout');
        }
        $this->assertDatabaseCount('orders', 1);
        $this->assertSame(5, $product->inventoryItem->fresh()->reserved);
    }

    public function test_buy_now_requires_login_and_rejects_unknown_or_another_users_intent(): void
    {
        $product = $this->product();
        Livewire::test(ProductAddToCart::class, ['product' => $product])->call('buyNow')->assertRedirect(route('login'));
        $this->assertEmpty(session('buy-now', []));
        $user = User::factory()->create();
        Livewire::actingAs($user)->test(ProductAddToCart::class, ['product' => $product])->call('buyNow');
        $token = array_key_first(session('buy-now'));
        $this->actingAs(User::factory()->create())->get(route('checkout.show', ['buy_now' => $token]))->assertNotFound();
        foreach (['invalid', (string) Str::uuid(), ['nested']] as $invalid) {
            $this->actingAs($user)->get(route('checkout.show', ['buy_now' => $invalid]))->assertNotFound();
        }
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_direct_checkout_intent_cannot_be_replaced_by_livewire_updates(): void
    {
        $product = $this->product();
        Livewire::actingAs(User::factory()->create())->test(ProductAddToCart::class, ['product' => $product])->call('buyNow');
        $token = array_key_first(session('buy-now'));
        $checkout = Livewire::withQueryParams(['buy_now' => $token])->test(CheckoutManager::class);
        $this->expectException(CannotUpdateLockedPropertyException::class);
        $checkout->set('buyNowToken', null);
    }

    public function test_unpublished_product_is_rechecked_when_confirming_direct_checkout(): void
    {
        $user = User::factory()->create();
        Address::factory()->for($user)->create(['is_primary' => true]);
        $product = $this->product();
        Livewire::actingAs($user)->test(ProductAddToCart::class, ['product' => $product])->call('buyNow');
        $token = array_key_first(session('buy-now'));
        $checkout = Livewire::withQueryParams(['buy_now' => $token])->test(CheckoutManager::class);
        $product->update(['status' => 'inactive']);
        $checkout->call('confirm')->assertHasErrors('checkout');
        $this->assertDatabaseCount('orders', 0);
        $this->assertSame(2, $product->inventoryItem->fresh()->reserved);
    }

    private function product(): Product
    {
        $product = Product::factory()->create();
        InventoryItem::factory()->for($product)->create(['on_hand' => 5, 'reserved' => 2]);

        return $product->load('inventoryItem');
    }
}
