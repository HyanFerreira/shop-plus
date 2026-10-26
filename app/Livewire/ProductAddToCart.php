<?php

namespace App\Livewire;

use App\Actions\Cart\AddCartItem;
use App\Enums\CatalogStatus;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;

class ProductAddToCart extends Component
{
    public Product $product;

    public $quantity = 1;

    #[Locked]
    public bool $compact = false;

    public string $status = '';

    public function increment(): void
    {
        $limit = min(99, $this->available());
        if ($limit > 0) {
            $this->quantity = min($limit, max(1, (filter_var($this->quantity, FILTER_VALIDATE_INT) ?: 1) + 1));
        }
    }

    public function decrement(): void
    {
        $limit = min(99, $this->available());
        if ($limit > 0) {
            $this->quantity = min($limit, max(1, (filter_var($this->quantity, FILTER_VALIDATE_INT) ?: 1) - 1));
        }
    }

    public function add(AddCartItem $action): mixed
    {
        if (! Auth::check()) {
            return $this->redirectRoute('login');
        }

        $this->status = '';
        $this->resetValidation();
        if (! $this->compact) {
            $this->validate(['quantity' => ['required', 'integer', 'min:1', 'max:99']]);
        }
        $action->execute(Auth::user(), $this->product, $this->compact ? 1 : $this->quantity);
        $this->status = 'Produto adicionado ao carrinho.';
        $this->dispatch('cart-updated');

        return null;
    }

    public function buyNow(): mixed
    {
        if (! Auth::check()) {
            return $this->redirectRoute('login');
        }

        $this->status = '';
        $this->resetValidation();
        $this->validate(['quantity' => ['required', 'integer', 'min:1', 'max:99']]);
        $product = $this->product->fresh('category');
        if (! $product || $product->status !== CatalogStatus::Active || $product->category?->status !== CatalogStatus::Active) {
            throw ValidationException::withMessages(['product' => 'Produto indisponível.']);
        }
        if ($this->quantity > $this->available()) {
            throw ValidationException::withMessages(['quantity' => 'Quantidade indisponível em estoque.']);
        }

        $token = (string) Str::uuid();
        session()->put('buy-now.'.$token, ['user_id' => Auth::id(), 'product_id' => $product->id, 'quantity' => (int) $this->quantity]);

        return $this->redirectRoute('checkout.show', ['buy_now' => $token]);
    }

    private function available(): int
    {
        return max(0, $this->product->inventoryItem()->first()?->available() ?? 0);
    }

    public function render()
    {
        $this->product->load('inventoryItem');

        return view($this->compact ? 'livewire.product-quick-add' : 'livewire.product-add-to-cart', ['available' => max(0, $this->product->inventoryItem?->available() ?? 0)]);
    }
}
