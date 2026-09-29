<?php

namespace App\Livewire;

use App\Actions\Checkout\CancelOrder;
use App\Actions\Payment\ProcessPayment;
use App\Enums\PaymentStatus;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;

class PaymentForm extends Component
{
    #[Locked]
    public int $orderId;

    #[Locked]
    public string $paymentKey;

    public string $cardholderName = '';

    public string $cardNumber = '';

    public string $cvv = '';

    public int $expiryMonth;

    public int $expiryYear;

    public function mount(Order $order): void
    {
        $this->orderId = $order->id;
        $this->paymentKey = (string) Str::uuid();
        $this->expiryMonth = now()->month;
        $this->expiryYear = now()->year + 1;
    }

    public function pay(ProcessPayment $action): mixed
    {
        $order = $this->ownedOrder();
        $key = 'payment:'.Auth::id().':'.$order->id;
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['payment' => 'Muitas tentativas. Aguarde antes de tentar novamente.']);
        }
        RateLimiter::hit($key, 60);

        try {
            $validated = $this->validate([
                'cardholderName' => ['required', 'string', 'min:3', 'max:100'],
                'cardNumber' => ['required', 'string', 'max:23'],
                'cvv' => ['required', 'digits_between:3,4'],
                'expiryMonth' => ['required', 'integer', 'between:1,12'],
                'expiryYear' => ['required', 'integer', 'between:'.now()->year.','.now()->addYears(15)->year],
                'paymentKey' => ['required', 'uuid'],
            ]);
            $payment = $action->execute(Auth::user(), $order, $validated['cardNumber'], $validated['cvv'], $validated['expiryMonth'], $validated['expiryYear'], $validated['paymentKey']);
        } finally {
            $this->reset('cardholderName', 'cardNumber', 'cvv');
        }

        session()->flash('payment-status', $payment->status === PaymentStatus::Authorized
            ? 'Pagamento fictício autorizado.'
            : 'Pagamento fictício recusado; a reserva foi liberada.');

        return $this->redirectRoute('orders.show', $order->public_number);
    }

    public function cancel(CancelOrder $action): mixed
    {
        $order = $action->execute(Auth::user(), $this->ownedOrder());
        session()->flash('payment-status', 'Pedido cancelado e reserva liberada.');

        return $this->redirectRoute('orders.show', $order->public_number);
    }

    private function ownedOrder(): Order
    {
        return Order::query()->where('user_id', Auth::id())->findOrFail($this->orderId);
    }

    public function render()
    {
        return view('livewire.payment-form', ['order' => $this->ownedOrder()]);
    }
}
