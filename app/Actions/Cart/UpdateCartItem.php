<?php

namespace App\Actions\Cart;

use App\Models\CartItem;
use App\Models\InventoryItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateCartItem
{
    public function execute(User $user, int $itemId, int $quantity): ?CartItem
    {
        return DB::transaction(function () use ($user, $itemId, $quantity) {
            $item = CartItem::query()->whereKey($itemId)->whereHas('cart', fn ($query) => $query->where('user_id', $user->id))->lockForUpdate()->first();
            if (! $item) {
                throw ValidationException::withMessages(['cart' => 'Item não encontrado.']);
            }
            if ($quantity === 0) {
                $item->delete();

                return null;
            }
            if ($quantity < 1 || $quantity > 99) {
                throw ValidationException::withMessages(['quantity' => 'Informe uma quantidade entre 1 e 99.']);
            }

            $available = InventoryItem::query()->where('product_id', $item->product_id)->value(DB::raw('on_hand - reserved')) ?? 0;
            if ($quantity > $available) {
                throw ValidationException::withMessages(['quantity' => 'Quantidade indisponível em estoque.']);
            }

            $item->update(['quantity' => $quantity]);

            return $item->refresh();
        }, 3);
    }
}
