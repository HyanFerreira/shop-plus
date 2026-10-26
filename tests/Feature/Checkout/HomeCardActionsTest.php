<?php

namespace Tests\Feature\Checkout;

use App\Livewire\HeaderCart;
use App\Livewire\ProductAddToCart;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Tests\TestCase;

class HomeCardActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_shared_cards_only_offer_favorites_and_product_details_keep_the_cart_action(): void
    {
        $product = $this->stockedProduct();
        $user = User::factory()->create();
        $user->favoriteProducts()->attach($product);
        $this->actingAs($user);

        foreach (['/', '/catalogo', '/favoritos'] as $url) {
            $response = $this->get($url)->assertOk();
            $document = new \DOMDocument;
            @$document->loadHTML('<?xml encoding="UTF-8">'.$response->getContent());
            $xpath = new \DOMXPath($document);
            $cards = $xpath->query('//article[contains(@class,"home-product-card")]');
            $this->assertGreaterThan(0, $cards->length);
            foreach ($cards as $card) {
                $this->assertSame(0, $xpath->query('.//*[contains(@class,"home-product-card__cart") or contains(@class,"product-quick-add")]', $card)->length);
                $this->assertSame(0, $xpath->query('.//button[not(contains(@class,"favorite-button"))]', $card)->length);
                $this->assertSame(1, $xpath->query('.//button[contains(@class,"favorite-button") and @aria-pressed="true"]', $card)->length);
                $this->assertSame(route('catalog.show', $product->slug), $xpath->query('.//a[contains(@class,"home-product-card__link")]', $card)->item(0)?->getAttribute('href'));
            }
        }

        $this->get(route('catalog.show', $product->slug))->assertOk()->assertSee('id="product-add-submit"', false);
    }

    public function test_compact_action_adds_exactly_one_unit_notifies_the_header_and_stays_on_the_page(): void
    {
        $product = $this->stockedProduct();
        $user = User::factory()->create();
        $other = User::factory()->create();
        $price = $product->price_cents;
        Livewire::actingAs($user)->test(ProductAddToCart::class, ['product' => $product, 'compact' => true])
            ->set('quantity', 99)->call('add')->assertHasNoErrors()->assertNoRedirect()
            ->assertDispatched('cart-updated')->assertSet('status', 'Produto adicionado ao carrinho.');
        $this->assertDatabaseHas('cart_items', ['product_id' => $product->id, 'quantity' => 1]);
        Livewire::test(HeaderCart::class)->assertViewHas('quantity', 1);
        Livewire::actingAs($other)->test(HeaderCart::class)->assertViewHas('quantity', 0);
        $this->assertSame($price, $product->fresh()->price_cents);
    }

    public function test_compact_mode_cannot_be_changed_from_the_client(): void
    {
        $component = Livewire::actingAs(User::factory()->create())->test(ProductAddToCart::class, [
            'product' => $this->stockedProduct(), 'compact' => true,
        ]);
        $this->expectException(CannotUpdateLockedPropertyException::class);
        $component->set('compact', false);
    }

    public function test_guest_follows_login_without_creating_cart_items(): void
    {
        Livewire::test(ProductAddToCart::class, ['product' => $this->stockedProduct(), 'compact' => true])
            ->assertSee(route('login'), false)->call('add')->assertRedirect(route('login'))->assertNotDispatched('cart-updated');
        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_stock_failure_after_render_preserves_cart_and_does_not_notify_the_header(): void
    {
        $product = $this->stockedProduct();
        $component = Livewire::actingAs(User::factory()->create())->test(ProductAddToCart::class, ['product' => $product, 'compact' => true]);
        $product->inventoryItem->update(['on_hand' => 0]);
        $component->call('add')->assertHasErrors('quantity')->assertNotDispatched('cart-updated')
            ->assertSee('Quantidade indisponível em estoque.')->assertSet('status', '');
        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_unavailable_button_is_disabled_and_hidden_product_is_rejected(): void
    {
        $product = $this->stockedProduct();
        $product->inventoryItem->update(['on_hand' => 0]);
        Livewire::actingAs(User::factory()->create())->test(ProductAddToCart::class, ['product' => $product->fresh(), 'compact' => true])
            ->assertSee('Indisponível')->assertSee('disabled', false);
        $product->update(['status' => 'inactive']);
        Livewire::test(ProductAddToCart::class, ['product' => $product, 'compact' => true])
            ->call('add')->assertHasErrors('product')->assertNotDispatched('cart-updated');
        $this->assertDatabaseCount('cart_items', 0);
    }

    private function stockedProduct(): Product
    {
        $product = Product::factory()->create();
        InventoryItem::factory()->for($product)->create(['on_hand' => 5, 'reserved' => 0]);

        return $product->load('inventoryItem');
    }
}
