<?php

namespace Tests\Feature\Payment;

use App\Actions\Cart\AddCartItem;
use App\Actions\Checkout\PlaceOrder;
use App\Domain\Payment\FictitiousCardValidator;
use App\Enums\OrderStatus;
use App\Livewire\PaymentForm;
use App\Models\Address;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class PaymentInterfaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_form_processes_test_card_and_clears_sensitive_state(): void
    {
        [$user, $order] = $this->pendingOrder();

        Livewire::actingAs($user)->test(PaymentForm::class, ['order' => $order])
            ->set('cardholderName', 'Cliente Fictício')
            ->set('cardNumber', FictitiousCardValidator::APPROVED_VISA)
            ->set('cvv', '123')
            ->set('expiryMonth', now()->month)
            ->set('expiryYear', now()->year + 1)
            ->call('pay')
            ->assertHasNoErrors()
            ->assertSet('cardNumber', '')
            ->assertSet('cvv', '')
            ->assertRedirect(route('orders.show', $order->public_number));

        $this->assertSame(OrderStatus::Paid, $order->fresh()->status);
    }

    public function test_order_page_exposes_only_masked_payment_data(): void
    {
        [$user, $order] = $this->pendingOrder();
        $this->actingAs($user)->get(route('orders.show', $order->public_number))
            ->assertOk()
            ->assertSee('4111 1111 1111 1111')
            ->assertSee('CVV de 3 dígitos');
    }

    public function test_invalid_sensitive_fields_are_cleared_after_validation(): void
    {
        [$user, $order] = $this->pendingOrder();

        Livewire::actingAs($user)->test(PaymentForm::class, ['order' => $order])
            ->set('cardholderName', 'Cliente Fictício')
            ->set('cardNumber', '1234')
            ->set('cvv', '12')
            ->call('pay')
            ->assertHasErrors(['cvv'])
            ->assertSet('cardNumber', '')
            ->assertSet('cvv', '');
    }

    public function test_component_can_cancel_pending_order(): void
    {
        [$user, $order] = $this->pendingOrder();

        Livewire::actingAs($user)->test(PaymentForm::class, ['order' => $order])
            ->call('cancel')
            ->assertRedirect(route('orders.show', $order->public_number));

        $this->assertSame(OrderStatus::Cancelled, $order->fresh()->status);
    }

    private function pendingOrder(): array
    {
        $user = User::factory()->create();
        $address = Address::factory()->for($user)->create();
        $product = Product::factory()->create();
        InventoryItem::factory()->for($product)->create(['on_hand' => 5]);
        app(AddCartItem::class)->execute($user, $product, 1);
        $order = app(PlaceOrder::class)->execute($user, $address->id, 'pickup', (string) Str::uuid());

        return [$user, $order];
    }
}
