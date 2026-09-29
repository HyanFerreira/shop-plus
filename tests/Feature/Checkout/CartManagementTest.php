<?php

namespace Tests\Feature\Checkout;

use App\Actions\Cart\AddCartItem;
use App\Actions\Cart\UpdateCartItem;
use App\Enums\CatalogStatus;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CartManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_items_are_server_scoped_and_prices_are_not_stored_in_cart(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['price_cents' => 1590]);
        InventoryItem::factory()->for($product)->create(['on_hand' => 10]);

        app(AddCartItem::class)->execute($user, $product, 2);
        $item = app(AddCartItem::class)->execute($user, $product, 1);

        $this->assertSame(3, $item->quantity);
        $this->assertSame($user->id, $item->cart->user_id);
        $this->assertArrayNotHasKey('price_cents', $item->getAttributes());
        $this->assertDatabaseCount('carts', 1);
    }

    public function test_stock_and_catalogue_availability_are_validated(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        InventoryItem::factory()->for($product)->create(['on_hand' => 2, 'reserved' => 1]);

        $this->expectException(ValidationException::class);
        app(AddCartItem::class)->execute($user, $product, 2);

        $product->update(['status' => CatalogStatus::Inactive]);
    }

    public function test_user_cannot_update_another_users_item(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $product = Product::factory()->create();
        InventoryItem::factory()->for($product)->create(['on_hand' => 5]);
        $item = app(AddCartItem::class)->execute($owner, $product, 1);

        $this->expectException(ValidationException::class);
        app(UpdateCartItem::class)->execute($other, $item->id, 2);
    }

    public function test_zero_quantity_removes_owned_item(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        InventoryItem::factory()->for($product)->create(['on_hand' => 5]);
        $item = app(AddCartItem::class)->execute($user, $product, 1);

        $this->assertNull(app(UpdateCartItem::class)->execute($user, $item->id, 0));
        $this->assertDatabaseMissing('cart_items', ['id' => $item->id]);
    }
}
