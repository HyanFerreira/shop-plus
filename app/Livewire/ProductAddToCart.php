<?php

namespace App\Livewire;

use App\Actions\Cart\AddCartItem;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ProductAddToCart extends Component
{
    public Product $product;

    public int $quantity = 1;

    public function increment(): void
    {
        $this->quantity = min(99, $this->quantity + 1);
    }

    public function decrement(): void
    {
        $this->quantity = max(1, $this->quantity - 1);
    }

    public function add(AddCartItem $action): mixed
    {
        if (! Auth::check()) {
            return $this->redirectRoute('login');
        }

        $this->validate(['quantity' => ['required', 'integer', 'min:1', 'max:99']]);
        $action->execute(Auth::user(), $this->product, $this->quantity);
        session()->flash('cart-status', 'Produto adicionado ao carrinho.');

        return $this->redirectRoute('cart.show');
    }

    public function render()
    {
        return view('livewire.product-add-to-cart');
    }
}
