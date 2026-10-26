<?php

namespace App\Livewire;

use App\Actions\Checkout\PlaceOrder;
use App\Domain\Checkout\ShippingCalculator;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;

class CheckoutManager extends Component
{
    public ?int $addressId = null;

    public string $shippingMethod = 'economy';

    public string $checkoutKey;

    #[Locked]
    public ?string $buyNowToken = null;

    public function mount(): void
    {
        $this->addressId = Auth::user()->addresses()->where('is_primary', true)->value('id')
            ?? Auth::user()->addresses()->value('id');
        if (request()->has('buy_now')) {
            $token = request()->query('buy_now');
            abort_unless(is_string($token) && Str::isUuid($token), 404);
            $this->buyNowToken = $token;
            $this->buyNowSelection();
        }
        $this->checkoutKey = $this->buyNowToken ?? (string) Str::uuid();
    }

    public function confirm(PlaceOrder $action): mixed
    {
        $rateLimitKey = 'checkout:'.Auth::id();
        if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            throw ValidationException::withMessages(['checkout' => 'Muitas tentativas. Aguarde antes de tentar novamente.']);
        }
        RateLimiter::hit($rateLimitKey, 60);

        $validated = $this->validate([
            'addressId' => ['required', 'integer'],
            'shippingMethod' => ['required', 'in:economy,express,pickup'],
            'checkoutKey' => ['required', 'uuid'],
        ]);
        $order = $action->execute(Auth::user(), $validated['addressId'], $validated['shippingMethod'], $this->buyNowToken ?? $validated['checkoutKey'], $this->buyNowSelection());

        return $this->redirectRoute('orders.show', $order->public_number);
    }

    public function render(ShippingCalculator $shipping)
    {
        $selection = $this->buyNowSelection();
        if ($selection) {
            $product = Product::with('images')->find($selection['product_id']);
            $items = $product ? collect([(new CartItem(['product_id' => $product->id, 'quantity' => $selection['quantity']]))->setRelation('product', $product)]) : collect();
            $cart = (new Cart)->setRelation('items', $items);
        } else {
            $cart = Cart::query()->where('user_id', Auth::id())->with('items.product.images')->first();
        }
        $address = $this->addressId ? Auth::user()->addresses()->find($this->addressId) : null;
        $weight = $cart?->items->sum(fn ($item) => $item->product->weight_grams * $item->quantity) ?? 0;
        $options = $address ? $shipping->options($address->postal_code_encrypted, $weight) : [];
        $subtotal = $cart?->items->sum(fn ($item) => $item->product->price_cents * $item->quantity) ?? 0;
        $lineTotals = $cart?->items->mapWithKeys(fn ($item) => [$item->id => $item->product->price_cents * $item->quantity])->all() ?? [];
        $selectedOption = $options[$this->shippingMethod] ?? null;
        $estimatedTotal = $selectedOption && $cart?->items->isNotEmpty() ? $subtotal + $selectedOption['price_cents'] : null;

        return view('livewire.checkout-manager', compact('cart', 'options', 'subtotal', 'lineTotals', 'selectedOption', 'estimatedTotal'));
    }

    private function buyNowSelection(): ?array
    {
        if ($this->buyNowToken === null) {
            return null;
        }
        $selection = session('buy-now.'.$this->buyNowToken);
        abort_unless(is_array($selection) && ($selection['user_id'] ?? null) === Auth::id(), 404);

        return $selection;
    }
}
