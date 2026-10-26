<?php

namespace Tests\Feature\Checkout;

use App\Actions\Cart\AddCartItem;
use App\Actions\Cart\UpdateCartItem;
use App\Livewire\CartManager;
use App\Livewire\HeaderCart;
use App\Livewire\ProductAddToCart;
use App\Models\CartItem;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class HeaderCartTest extends TestCase
{
    use RefreshDatabase;

    public function test_empty_cart_has_no_badge_and_total_counts_only_the_current_users_units(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        Livewire::test(HeaderCart::class)->assertSee('aria-label="Carrinho"', false)->assertDontSee('storefront-cart__badge', false);
        $first = $this->item($user, 2);
        $this->item($user, 3);
        $this->item(User::factory()->create(), 9);
        $header = Livewire::test(HeaderCart::class)->assertViewHas('quantity', 5)->assertSee('aria-label="Carrinho, 5 itens"', false)->assertSee('storefront-cart__badge', false);
        app(UpdateCartItem::class)->execute($user, $first->id, 0);
        $header->dispatch('cart-updated')->assertViewHas('quantity', 3);
    }

    public function test_saved_quantity_changes_and_removals_notify_the_header_but_invalid_updates_do_not(): void
    {
        $user = User::factory()->create();
        $item = $this->item($user, 2);
        Livewire::actingAs($user)->test(CartManager::class)->set('quantities.'.$item->id, 4)
            ->call('updateItem', $item->id)->assertDispatched('cart-updated');
        Livewire::test(HeaderCart::class)->assertViewHas('quantity', 4);
        Livewire::test(CartManager::class)->set('quantities.'.$item->id, 99)
            ->call('updateItem', $item->id)->assertHasErrors('quantity')->assertNotDispatched('cart-updated');
        Livewire::test(CartManager::class)->call('removeItem', $item->id)->assertDispatched('cart-updated');
        Livewire::test(HeaderCart::class)->assertViewHas('quantity', 0)->assertDontSee('storefront-cart__badge', false);
    }

    public function test_product_addition_keeps_redirect_and_header_reads_the_saved_quantity(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        InventoryItem::factory()->for($product)->create(['on_hand' => 20, 'reserved' => 0]);
        Livewire::actingAs($user)->test(ProductAddToCart::class, ['product' => $product])
            ->set('quantity', 1)->call('add')->assertRedirect(route('cart.show'));
        $this->get(route('cart.show'))->assertOk()->assertSee('aria-label="Carrinho, 1 item"', false);
    }

    public function test_header_uses_one_aggregate_query_per_render(): void
    {
        $this->actingAs(User::factory()->create());
        DB::enableQueryLog();
        Livewire::test(HeaderCart::class);
        $queries = array_filter(DB::getQueryLog(), fn ($query) => str_contains($query['query'], 'cart_items'));
        DB::disableQueryLog();
        $this->assertCount(1, $queries);
        $this->assertStringContainsString('sum(', array_values($queries)[0]['query']);
    }

    private function item(User $user, int $quantity): CartItem
    {
        $product = Product::factory()->create();
        InventoryItem::factory()->for($product)->create(['on_hand' => 20, 'reserved' => 0]);

        return app(AddCartItem::class)->execute($user, $product, $quantity);
    }
}
