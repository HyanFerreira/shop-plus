<?php

namespace App\Livewire;

use App\Actions\Checkout\PlaceOrder;
use App\Domain\Checkout\ShippingCalculator;
use App\Models\Cart;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class CheckoutManager extends Component
{
    public ?int $addressId = null;

    public string $shippingMethod = 'economy';

    public string $checkoutKey;

    public function mount(): void
    {
        $this->addressId = Auth::user()->addresses()->where('is_primary', true)->value('id')
            ?? Auth::user()->addresses()->value('id');
        $this->checkoutKey = (string) Str::uuid();
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
        $order = $action->execute(Auth::user(), $validated['addressId'], $validated['shippingMethod'], $validated['checkoutKey']);

        return $this->redirectRoute('orders.show', $order->public_number);
    }

    public function render(ShippingCalculator $shipping)
    {
        $cart = Cart::query()->where('user_id', Auth::id())->with('items.product')->first();
        $address = $this->addressId ? Auth::user()->addresses()->find($this->addressId) : null;
        $weight = $cart?->items->sum(fn ($item) => $item->product->weight_grams * $item->quantity) ?? 0;
        $options = $address ? $shipping->options($address->postal_code_encrypted, $weight) : [];
        $subtotal = $cart?->items->sum(fn ($item) => $item->product->price_cents * $item->quantity) ?? 0;

        return view('livewire.checkout-manager', compact('cart', 'options', 'subtotal'));
    }
}
