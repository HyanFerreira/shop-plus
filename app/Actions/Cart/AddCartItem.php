<?php

namespace App\Actions\Cart;

use App\Enums\CatalogStatus;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AddCartItem
{
    public function execute(User $user, Product $product, int $quantity): CartItem
    {
        if ($quantity < 1 || $quantity > 99) {
            throw ValidationException::withMessages(['quantity' => 'Informe uma quantidade entre 1 e 99.']);
        }

        return DB::transaction(function () use ($user, $product, $quantity) {
            $product = Product::query()->with('category')->lockForUpdate()->findOrFail($product->id);
            if ($product->status !== CatalogStatus::Active || $product->category?->status !== CatalogStatus::Active) {
                throw ValidationException::withMessages(['product' => 'Produto indisponÃ­vel.']);
            }

            $cart = Cart::query()->firstOrCreate(['user_id' => $user->id]);
            $item = $cart->items()->where('product_id', $product->id)->lockForUpdate()->first();
            $newQuantity = ($item?->quantity ?? 0) + $quantity;
            $available = InventoryItem::query()->where('product_id', $product->id)->value(DB::raw('on_hand - reserved')) ?? 0;

            if ($newQuantity > $available) {
                throw ValidationException::withMessages(['quantity' => 'Quantidade indisponÃ­vel em estoque.']);
            }

            $item ??= new CartItem(['product_id' => $product->id]);
            $item->quantity = $newQuantity;
            $cart->items()->save($item);

            return $item->refresh();
        }, 3);
    }
}
