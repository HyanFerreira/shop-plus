<?php

namespace Tests\Feature\Payment;

use App\Actions\Cart\AddCartItem;
use App\Actions\Checkout\PlaceOrder;
use App\Livewire\PaymentForm;
use App\Models\Address;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class PaymentPresentationTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_order_exposes_payment_step_before_details_without_new_card_persistence(): void
    {
        [$user, $order] = $this->pending();
        $this->actingAs($user)->get(route('orders.show', $order->public_number))->assertOk()
            ->assertSee('payment-layout', false)->assertSeeText('Etapa 2 de 2')->assertSeeText('Não use dados reais')
            ->assertSeeInOrder(['wire:submit="pay"', 'Itens'], false)->assertSee($order->public_number)
            ->assertSee('wire:confirm="Cancelar o pedido e liberar a reserva?"', false);
        $this->actingAs(User::factory()->create())->get(route('orders.show', $order->public_number))->assertNotFound();
    }

    public function test_validation_errors_are_associated_with_fields_and_sensitive_state_is_cleared(): void
    {
        [$user, $order] = $this->pending();
        Livewire::actingAs($user)->test(PaymentForm::class, ['order' => $order])->set('cardholderName', 'Cliente Fictício')
            ->set('cardNumber', '1234')->set('cvv', '12')->call('pay')->assertHasErrors('cvv')
            ->assertSee('id="payment-error-cvv"', false)->assertSee('aria-invalid="true"', false)
            ->assertSee('wire:target="pay,cancel"', false)->assertSet('cardNumber', '')->assertSet('cvv', '');
        $this->assertDatabaseCount('payments', 0);
    }

    private function pending(): array
    {
        $user = User::factory()->create();
        $address = Address::factory()->for($user)->create();
        $product = Product::factory()->create();
        InventoryItem::factory()->for($product)->create(['on_hand' => 5, 'reserved' => 0]);
        app(AddCartItem::class)->execute($user, $product, 1);
        return [$user, app(PlaceOrder::class)->execute($user, $address->id, 'pickup', (string) Str::uuid())];
    }
}
