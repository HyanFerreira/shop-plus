<?php

namespace Tests\Feature\Checkout;

use App\Actions\Cart\AddCartItem;
use App\Livewire\CartManager;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class CartPresentationTest extends TestCase
{
    use RefreshDatabase;

    public function test_empty_cart_has_illustration_and_catalog_action_while_guests_still_redirect(): void
    {
        $this->get(route('cart.show'))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->get(route('cart.show'))->assertOk()
            ->assertSee('cart-empty__art', false)->assertSeeText('Seu carrinho está vazio.')
            ->assertSee(route('catalog.index'), false)->assertSee('cart-container', false);
    }

    public function test_cart_uses_registered_available_images_before_local_demo_fallback(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        [$product] = $this->item($user, ['sku' => 'NIM-PHONE-01', 'brand' => 'Marca <teste>']);
        $product->images()->create(['path' => 'missing.svg', 'sort_order' => 0]);
        $product->images()->create(['path' => 'registered.svg', 'sort_order' => 1, 'alt_text' => 'Foto registrada']);
        Storage::disk('public')->put('registered.svg', '<svg xmlns="http://www.w3.org/2000/svg"/>');
        Livewire::actingAs($user)->test(CartManager::class)->assertSee('registered.svg')->assertSee('Foto registrada')
            ->assertSee($product->brand)->assertDontSee($product->brand, false)->assertDontSee('images/demo-products/phone.svg', false);
        Storage::disk('public')->delete('registered.svg');
        Livewire::actingAs($user)->test(CartManager::class)->assertSee('images/demo-products/phone.svg', false);
        $product->update(['sku' => 'UNKNOWN-CART']);
        Livewire::actingAs($user)->test(CartManager::class)->assertSeeText('Imagem indisponível');
    }

    public function test_summary_uses_server_subtotal_and_does_not_claim_final_shipping(): void
    {
        $user = User::factory()->create();
        [$product, $item] = $this->item($user, ['price_cents' => 12345], 2);
        $this->item($user, ['price_cents' => 5000]);
        $component = Livewire::actingAs($user)->test(CartManager::class)->assertViewHas('subtotal', 29690)
            ->assertSeeText('R$ 296,90')->assertSeeText('Resumo do pedido')->assertSeeText('Total dos produtos')
            ->assertSeeText('A definir no checkout')->assertDontSeeText('R$ 0,00')->assertDontSeeText('Cupom')
            ->assertSee(route('checkout.show'), false);
        $component->set('quantities.'.$item->id, 3)->call('updateItem', $item->id)
            ->assertViewHas('subtotal', 42035)->assertSeeText('R$ 420,35');
        $this->assertDatabaseHas('cart_items', ['id' => $item->id, 'quantity' => 3]);
        $this->assertSame(12345, $product->fresh()->price_cents);
    }

    public function test_controls_save_automatically_and_remove_without_a_text_button(): void
    {
        $user = User::factory()->create();
        [, $item] = $this->item($user, [], 2);
        $component = Livewire::actingAs($user)->test(CartManager::class)
            ->assertSee('wire:model="quantities.'.$item->id.'"', false)
            ->assertSee('saveQuantity('.$item->id.', $event.target.value)', false)
            ->assertDontSee('readonly', false)
            ->assertSee('removeItem('.$item->id.')', false)
            ->assertDontSee('>Atualizar</button>', false)
            ->assertSee('title="Remover', false)
            ->assertSee('Aumentar quantidade')->assertSee('Diminuir quantidade')
            ->assertSee('cart-item-'.$item->id)->assertSee('wire:loading.attr="disabled"', false);
        $component->set('quantities.'.$item->id, 0)->call('updateItem', $item->id)->assertSeeText('Seu carrinho está vazio.');
        $this->assertDatabaseMissing('cart_items', ['id' => $item->id]);
        [, $second] = $this->item($user);
        Livewire::actingAs($user)->test(CartManager::class)->call('removeItem', $second->id)->assertSeeText('Seu carrinho está vazio.');
        $this->assertDatabaseMissing('cart_items', ['id' => $second->id]);
    }

    public function test_clear_cart_only_removes_owned_items_without_changing_stock(): void
    {
        $user = User::factory()->create();
        [$product] = $this->item($user, [], 2);
        $this->item($user);
        $other = User::factory()->create();
        [, $foreign] = $this->item($other);
        $stock = $product->inventoryItem->getAttributes();
        Livewire::actingAs($user)->test(CartManager::class)->call('clearCart')
            ->assertHasNoErrors()->assertSet('quantities', [])->assertDispatched('cart-updated')
            ->assertSeeText('Seu carrinho está vazio.')->call('clearCart')->assertHasNoErrors();
        $this->assertSame(0, $user->cart->items()->count());
        $this->assertDatabaseHas('cart_items', ['id' => $foreign->id, 'quantity' => 1]);
        $this->assertSame($stock, $product->inventoryItem->fresh()->getAttributes());
    }

    public function test_existing_stock_and_invalid_quantity_errors_are_visible_without_changing_saved_values(): void
    {
        $user = User::factory()->create();
        [, $item] = $this->item($user);
        Livewire::actingAs($user)->test(CartManager::class)->set('quantities.'.$item->id, 6)
            ->call('updateItem', $item->id)->assertHasErrors('quantity')->assertSeeText('Quantidade indisponível em estoque.');
        Livewire::actingAs($user)->test(CartManager::class)->set('quantities.'.$item->id, 'inválida')
            ->call('updateItem', $item->id)->assertHasErrors('quantities.'.$item->id)->assertSeeText('Quantidade inválida.');
        $other = User::factory()->create();
        Livewire::actingAs($other)->test(CartManager::class)->call('removeItem', $item->id)
            ->assertHasErrors('cart')->assertSeeText('Item não encontrado.');
        $this->assertDatabaseHas('cart_items', ['id' => $item->id, 'quantity' => 1]);
    }

    private function item(User $user, array $attributes = [], int $quantity = 1): array
    {
        $product = Product::factory()->create($attributes);
        InventoryItem::factory()->for($product)->create(['on_hand' => 7, 'reserved' => 2]);

        return [$product, app(AddCartItem::class)->execute($user, $product, $quantity)];
    }
}
