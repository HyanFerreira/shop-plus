<?php

namespace App\Livewire;

use App\Actions\Cart\UpdateCartItem;
use App\Models\Cart;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class CartManager extends Component
{
    /** @var array<int, int|string> */
    public array $quantities = [];

    public function mount(): void
    {
        $this->loadQuantities();
    }

    public function updateItem(UpdateCartItem $action, int $itemId): bool
    {
        $quantity = filter_var($this->quantities[$itemId] ?? null, FILTER_VALIDATE_INT);
        if ($quantity === false) {
            $this->addError('quantities.'.$itemId, 'Quantidade inválida.');

            return false;
        }
        $action->execute(Auth::user(), $itemId, $quantity);
        $this->loadQuantities();
        $this->dispatch('cart-updated');

        return true;
    }

    public function removeItem(UpdateCartItem $action, int $itemId): void
    {
        $action->execute(Auth::user(), $itemId, 0);
        $this->loadQuantities();
        $this->dispatch('cart-updated');
    }

    private function loadQuantities(): void
    {
        $this->quantities = Cart::query()->where('user_id', Auth::id())->first()?->items()->pluck('quantity', 'id')->all() ?? [];
    }

    public function clearCart(UpdateCartItem $action): void
    {
        DB::transaction(function () use ($action) {
            $cart = Cart::query()->where('user_id', Auth::id())->first();
            foreach ($cart?->items()->orderBy('id')->lockForUpdate()->pluck('id') ?? [] as $itemId) {
                $action->execute(Auth::user(), $itemId, 0);
            }
        }, 3);
        $this->loadQuantities();
        $this->dispatch('cart-updated');
    }

    public function render()
    {
        $cart = Cart::query()->where('user_id', Auth::id())->with(['items.product.images', 'items.product.inventoryItem', 'items.product.category'])->first();
        $subtotal = $cart?->items->sum(fn ($item) => $item->product->price_cents * $item->quantity) ?? 0;

        return view('livewire.cart-manager', compact('cart', 'subtotal'));
    }
}
