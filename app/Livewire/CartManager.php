<?php

namespace App\Livewire;

use App\Actions\Cart\UpdateCartItem;
use App\Models\Cart;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class CartManager extends Component
{
    /** @var array<int, int|string> */
    public array $quantities = [];

    public function mount(): void
    {
        $this->loadQuantities();
    }

    public function updateItem(UpdateCartItem $action, int $itemId): void
    {
        $quantity = filter_var($this->quantities[$itemId] ?? null, FILTER_VALIDATE_INT);
        if ($quantity === false) {
            $this->addError('quantities.'.$itemId, 'Quantidade inválida.');

            return;
        }
        $action->execute(Auth::user(), $itemId, $quantity);
        $this->loadQuantities();
    }

    public function removeItem(UpdateCartItem $action, int $itemId): void
    {
        $action->execute(Auth::user(), $itemId, 0);
        $this->loadQuantities();
    }

    private function loadQuantities(): void
    {
        $this->quantities = Cart::query()->where('user_id', Auth::id())->first()?->items()->pluck('quantity', 'id')->all() ?? [];
    }

    public function render()
    {
        $cart = Cart::query()->where('user_id', Auth::id())->with(['items.product.images', 'items.product.inventoryItem'])->first();
        $subtotal = $cart?->items->sum(fn ($item) => $item->product->price_cents * $item->quantity) ?? 0;

        return view('livewire.cart-manager', compact('cart', 'subtotal'));
    }
}
